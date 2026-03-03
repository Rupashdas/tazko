<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordMail;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class AuthController extends Controller {
    public function register(RegisterRequest $request): JsonResponse {

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->preference()->create([]);
        Auth::login($user);

        // Regenerate session to prevent fixation
        $request->session()->regenerate();

        return response()->json([
            'status' => 'success',
            'message' => 'User registered successfully',
            'user' => new UserResource($user),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse {

        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials)) {
            return response()->json([
                'status' => 'error',
                'field' => 'password',
                'message' => 'Incorrect password'
            ], 401);
        }

        $request->session()->regenerate();

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'user' => new UserResource(Auth::user()),
        ]);
    }
    public function user(Request $request): JsonResponse {
        // Eager-load roles AND their capabilities
        $user = $request->user()->load('roles.capabilities');

        return response()->json([
            'user' => new UserResource($user),
        ]);
    }
    public function logout(Request $request): JsonResponse {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully',
            'user' => null
        ]);
    }

    public function sendResetLinkEmail(Request $request): JsonResponse {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'No user found with this email',
                'errors' => [
                    'email' => ["The provided email address doesn't exist."]
                ]
            ], 400);
        }

        $token = Password::broker()->createToken($user);

        $frontend = config('app.frontend_url') ?? config('app.url');
        $link = rtrim($frontend, '/') . '/reset-password?token=' . $token . '&email=' . urlencode($user->email);

        Mail::to($user->email)->send(new ResetPasswordMail($link));

        return response()->json(['status' => 'success', 'message' => 'Your password reset link was sent to your email'], 200);
    }

    // Handle resetting the user's password
    public function resetPassword(Request $request): JsonResponse {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return response()->json(['status' => 'success', 'message' => __($status)]);
        }

        return response()->json(['status' => 'error', 'message' => __($status)], 400);
    }

    /**
     * Also update updateProfile() to return roles.capabilities:
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse {
        $user = auth()->user();

        $data = $request->only(['name', 'email', 'title', 'phone', 'bio', 'location']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = $path;
        }

        $user->update($data);

        // Reload with roles.capabilities for updated resource
        $user->load('roles.capabilities');

        return response()->json([
            'status'  => 'success',
            'message' => 'Profile updated successfully',
            'user'    => new UserResource($user),
        ]);
    }

    public function removeAvatar(): JsonResponse {
        $user = auth()->user();
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }
        $user->avatar = null;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Avatar removed successfully',
            'user' => new UserResource($user)
        ]);
    }
}
