<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserPreferenceController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\CapabilityController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\ProjectController;

// ── Public ────────────────────────────────────────────────────────────────────
Route::post('/register',        [AuthController::class, 'register']);
Route::post('/login',           [AuthController::class, 'login']);
Route::post('/password/email',  [AuthController::class, 'sendResetLinkEmail']);
Route::post('/password/reset',  [AuthController::class, 'resetPassword']);

Route::get('/invitations/{token}',         [InvitationController::class, 'show']);
Route::post('/invitations/{token}/accept', [InvitationController::class, 'accept']);

// ── Authenticated ─────────────────────────────────────────────────────────────
// 'active' middleware ensures inactive users are kicked out on every request
Route::middleware(['auth:sanctum', 'active'])->group(function () {

    Route::get('/user',           [AuthController::class, 'user']);
    Route::post('/logout',        [AuthController::class, 'logout']);
    Route::post('/user',          [AuthController::class, 'updateProfile']);
    Route::post('/remove-avatar', [AuthController::class, 'removeAvatar']);
    Route::post('/upload-avatar', [AuthController::class, 'uploadAvatar']);

    Route::get('/preferences',  [UserPreferenceController::class, 'show']);
    Route::post('/preferences', [UserPreferenceController::class, 'store']);

    Route::get('/capabilities', [CapabilityController::class, 'index']);
    Route::apiResource('roles', RoleController::class);

    Route::apiResource('users', UserController::class);
    Route::patch('/users/{user}/role',   [UserController::class, 'assignRole']);
    Route::patch('/users/{user}/active', [UserController::class, 'toggleActive']);


    Route::post('/invitations',                         [InvitationController::class, 'store']);
    Route::get('/invitations',                          [InvitationController::class, 'index']);
    Route::post('/invitations/{invitation}/resend',     [InvitationController::class, 'resend']);
    Route::delete('/invitations/{invitation}',          [InvitationController::class, 'destroy']);


    // Static project routes MUST come before apiResource so {project} wildcard doesn't swallow them
    Route::get('/projects/archived',              [ProjectController::class, 'archivedIndex']);
    Route::patch('/projects/{project}/archive',   [ProjectController::class, 'archive']);
    Route::patch('/projects/{project}/restore',   [ProjectController::class, 'restore']);
    Route::apiResource('projects', ProjectController::class)->only(['index', 'store', 'update', 'destroy']);
});
