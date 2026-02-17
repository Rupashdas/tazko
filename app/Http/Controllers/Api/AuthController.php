<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
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


class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);

        // Regenerate session to prevent fixation
        $request->session()->regenerate();

        return response()->json([
            'status' => 'success',
            'message' => 'User registered successfully',
            'user' => new UserResource($user),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {

        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials)) {
            return response()->json([
                'status' => 'error',
                'message' => 'The provided credentials are invalid.'
            ], 401);
        }

        // Regenerate session to avoid fixation
        $request->session()->regenerate();

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'user' => new UserResource(Auth::user()),
        ]);
    }
    public function user(Request $request): JsonResponse
    {
        $user = auth()->user();

        return response()->json([
            'user' => $user ? new UserResource($user) : null
        ]);
    }
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully',
            'user' => null
        ]);
    }

    public function sendResetLinkEmail(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'No user found with this email'], 400);
        }

        $token = Password::broker()->createToken($user);

        $frontend = config('app.frontend_url') ?? config('app.url');
        $link = rtrim($frontend, '/') . '/reset-password?token=' . $token . '&email=' . urlencode($user->email);

        Mail::to($user->email)->send(new ResetPasswordMail($link));

        return response()->json(['status' => 'success', 'message' => 'Your password reset link was sent to your email'], 200 );
    }

    // Handle resetting the user's password
    public function resetPassword(Request $request): JsonResponse
    {
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

}
