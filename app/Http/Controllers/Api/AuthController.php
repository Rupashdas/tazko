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

        $user->load('roles.capabilities', 'preference');

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
                'status'  => 'error',
                'message' => 'Invalid credentials.',
            ], 401);
        }

        // ── Block inactive users immediately after credential check ──
        if (! Auth::user()->is_active) {
            Auth::logout();
            return response()->json([
                'status'  => 'error',
                'code'    => 'account_deactivated',
                'field'   => 'email',
                'message' => 'Your account has been deactivated. Please contact your administrator.',
            ], 403);
        }

        $request->session()->regenerate();

        $user = Auth::user()->load('roles.capabilities', 'preference');

        return response()->json([
            'status'  => 'success',
            'message' => 'Login successful',
            'user'    => new UserResource($user),
        ]);
    }

    public function user(Request $request): JsonResponse {

        $user = $request->user()->load('roles.capabilities', 'preference');

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

        // Always return the same response regardless of whether the email exists,
        // to prevent account enumeration. Only send the email if the user is real.
        if ($user) {
            $token = Password::broker()->createToken($user);

            $frontend = config('app.frontend_url') ?? config('app.url');
            $link = rtrim($frontend, '/') . '/reset-password?token=' . $token . '&email=' . urlencode($user->email);

            Mail::to($user->email)->send(new ResetPasswordMail($link));
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'If that email is registered, a password reset link has been sent.',
        ], 200);
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

        // Only non-password fields are always allowed (guarded by users.profile.update above)
        $data = $request->only(['name', 'email', 'title', 'phone', 'bio', 'location']);

        // Password change requires an extra capability check
        if ($request->filled('password')) {
            if (!$user->hasCapability('users.password.change')) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'You do not have permission to change your password.',
                ], 403);
            }
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
        $user->load('roles.capabilities', 'preference');

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
        $user->load('roles.capabilities', 'preference');

        return response()->json([
            'status' => 'success',
            'message' => 'Avatar removed successfully',
            'user' => new UserResource($user)
        ]);
    }

    public function uploadAvatar(Request $request): JsonResponse {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $user = auth()->user();

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->avatar = $path;
        $user->save();

        $user->load('roles.capabilities', 'preference');

        return response()->json([
            'status'  => 'success',
            'message' => 'Avatar updated successfully',
            'user'    => new UserResource($user),
        ]);
    }
}
