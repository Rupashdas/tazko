<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserPreferenceController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\CapabilityController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectMemberController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\SubtaskController;
use App\Http\Controllers\Api\LabelController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\TaskCommentController;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AttachmentShareController;
use App\Http\Controllers\Api\PublicShareController;

// ── Public ────────────────────────────────────────────────────────────────────
// Rate-limited to mitigate brute-force and abuse.
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/register',        [AuthController::class, 'register']);
    Route::post('/login',           [AuthController::class, 'login']);
    Route::post('/password/email',  [AuthController::class, 'sendResetLinkEmail']);
    Route::post('/password/reset',  [AuthController::class, 'resetPassword']);

    Route::post('/invitations/{token}/accept', [InvitationController::class, 'accept']);
});

Route::get('/invitations/{token}', [InvitationController::class, 'show']);

// Public attachment share links. No auth — access is gated by token
// validity (enabled + not expired) inside the controller. URL shape:
//   GET /api/share/{token}             → stream inline
//   GET /api/share/{token}?download=1  → stream as attachment (if allow_download)
Route::get('/share/{token}', [PublicShareController::class, 'show'])->name('share.show');

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
    Route::apiResource('projects', ProjectController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::post('/projects/{project}/members',           [ProjectMemberController::class, 'store']);
    Route::delete('/projects/{project}/members/{user}', [ProjectMemberController::class, 'destroy']);
    Route::get('/projects/{project}/tasks',                  [TaskController::class, 'index']);
    Route::post('/projects/{project}/tasks',                 [TaskController::class, 'store']);
    Route::post('/projects/{project}/tasks/reorder',         [TaskController::class, 'reorder']);
    Route::get('/projects/{project}/tasks/{task}',           [TaskController::class, 'show']);
    Route::patch('/projects/{project}/tasks/{task}',         [TaskController::class, 'update']);
    Route::delete('/projects/{project}/tasks/{task}',        [TaskController::class, 'destroy']);

    // ── Subtasks ─────────────────────────────────────────────────────────────
    Route::post('/projects/{project}/tasks/{task}/subtasks/reorder',                [SubtaskController::class, 'reorder']);
    Route::post('/projects/{project}/tasks/{task}/subtasks',                        [SubtaskController::class, 'store']);
    Route::patch('/projects/{project}/tasks/{task}/subtasks/{subtask}',             [SubtaskController::class, 'update']);
    Route::delete('/projects/{project}/tasks/{task}/subtasks/{subtask}',            [SubtaskController::class, 'destroy']);

    // ── Labels (project-scoped palette) ──────────────────────────────────────
    Route::get('/projects/{project}/labels',                                        [LabelController::class, 'index']);
    Route::post('/projects/{project}/labels',                                       [LabelController::class, 'store']);
    Route::delete('/projects/{project}/labels/{label}',                             [LabelController::class, 'destroy']);

    Route::get('/projects/{project}/tasks/{task}/comments',                          [TaskCommentController::class, 'index']);
    Route::post('/projects/{project}/tasks/{task}/comments',                         [TaskCommentController::class, 'store']);
    Route::patch('/projects/{project}/tasks/{task}/comments/{comment}',              [TaskCommentController::class, 'update']);
    Route::delete('/projects/{project}/tasks/{task}/comments/{comment}',             [TaskCommentController::class, 'destroy']);
    Route::post('/projects/{project}/tasks/{task}/comments/{comment}/like',          [TaskCommentController::class, 'toggleLike']);

    Route::get('/projects/{project}/comments',                          [CommentController::class, 'index']);
    Route::post('/projects/{project}/comments',                         [CommentController::class, 'store']);
    Route::patch('/projects/{project}/comments/{comment}',              [CommentController::class, 'update']);
    Route::delete('/projects/{project}/comments/{comment}',             [CommentController::class, 'destroy']);
    Route::post('/projects/{project}/comments/{comment}/like',          [CommentController::class, 'toggleLike']);

    // ── Attachments ──────────────────────────────────────────────────────────
    // Files-tab + upload endpoints sit under their owning project.
    Route::post('/projects/{project}/attachments', [AttachmentController::class, 'store'])
        ->middleware(['can:upload-attachment,project', 'project.not_archived']);
    Route::get('/projects/{project}/attachments',  [AttachmentController::class, 'index']);

    // Per-attachment routes. Access control runs inside the controller via
    // $this->authorize(...) so we can return tailored 403 messages.
    Route::get('/attachments/{attachment}/stream', [AttachmentController::class, 'stream'])
        ->name('attachments.stream');
    Route::delete('/attachments/{attachment}',     [AttachmentController::class, 'destroy']);

    // ── Attachment shares ────────────────────────────────────────────────────
    // Create + list nested under the parent attachment.
    Route::post('/attachments/{attachment}/shares', [AttachmentShareController::class, 'store']);
    Route::get('/attachments/{attachment}/shares',  [AttachmentShareController::class, 'index']);

    // Mutate / revoke specific share rows at the top level.
    Route::patch('/attachment-shares/{share}',  [AttachmentShareController::class, 'update']);
    Route::delete('/attachment-shares/{share}', [AttachmentShareController::class, 'destroy']);
});
