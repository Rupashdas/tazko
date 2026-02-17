<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
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

}
