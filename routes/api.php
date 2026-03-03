<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserPreferenceController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\CapabilityController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\InvitationController;

// ── Public ────────────────────────────────────────────────────────────────────
Route::post('/register',        [AuthController::class, 'register']);
Route::post('/login',           [AuthController::class, 'login']);
Route::post('/password/email',  [AuthController::class, 'sendResetLinkEmail']);
Route::post('/password/reset',  [AuthController::class, 'resetPassword']);

Route::get('/invitations/{token}',         [InvitationController::class, 'show']);
Route::post('/invitations/{token}/accept', [InvitationController::class, 'accept']);

// ── Authenticated ─────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth & own profile
    Route::get('/user',           [AuthController::class, 'user']);
    Route::post('/logout',        [AuthController::class, 'logout']);
    Route::post('/user',          [AuthController::class, 'updateProfile']);
    Route::post('/remove-avatar', [AuthController::class, 'removeAvatar']);
    Route::post('/upload-avatar', [AuthController::class, 'uploadAvatar']);

    // Preferences
    Route::get('/preferences',  [UserPreferenceController::class, 'show']);
    Route::post('/preferences', [UserPreferenceController::class, 'store']);

    // ── System Settings ────────────────────────────────────────────────────
    Route::get('/capabilities', [CapabilityController::class, 'index']);
    Route::apiResource('roles', RoleController::class);

    // ── Users Management ───────────────────────────────────────────────────
    // show() is now included — guarded by users.profile.view
    Route::apiResource('users', UserController::class);
    Route::patch('/users/{user}/role',   [UserController::class, 'assignRole']);
    Route::patch('/users/{user}/active', [UserController::class, 'toggleActive']);

    // ── Invitations ────────────────────────────────────────────────────────
    Route::post('/invitations',                        [InvitationController::class, 'store']);
    Route::post('/invitations/{invitation}/resend',    [InvitationController::class, 'resend']);
    Route::delete('/invitations/{invitation}',         [InvitationController::class, 'destroy']);
});
