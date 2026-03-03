<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserPreferenceController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\CapabilityController;

// ── Public ────────────────────────────────────────────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/password/email', [AuthController::class, 'sendResetLinkEmail']);
Route::post('/password/reset', [AuthController::class, 'resetPassword']);

// ── Authenticated ─────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth & profile
    Route::get('/user',           [AuthController::class, 'user']);
    Route::post('/logout',        [AuthController::class, 'logout']);
    Route::post('/user',          [AuthController::class, 'updateProfile']);
    Route::post('/remove-avatar', [AuthController::class, 'removeAvatar']);

    // Preferences
    Route::get('/preferences',  [UserPreferenceController::class, 'show']);
    Route::post('/preferences', [UserPreferenceController::class, 'store']);

    // ── System Settings ────────────────────────────────────────────────────
    Route::get('/capabilities', [CapabilityController::class, 'index']);

    Route::apiResource('roles', RoleController::class);
});
