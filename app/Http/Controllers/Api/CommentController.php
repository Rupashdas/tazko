<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\CommentLike;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller implements HasMiddleware {
    public static function middleware(): array {
        return [
            new Middleware('project.member'),
            new Middleware('project.not_archived', only: ['store', 'update', 'destroy', 'toggleLike']),
            new Middleware('capability:comments.view',   only: ['index']),
            new Middleware('capability:comments.create', only: ['store']),
            new Middleware('capability:comments.update', only: ['update']),
            new Middleware('capability:comments.delete', only: ['destroy']),
            new Middleware('capability:comments.react',  only: ['toggleLike']),
        ];
    }

    /**
     * GET /api/projects/{project}/comments
     */
    public function index(Project $project): JsonResponse {
        $authId = auth()->id();

        $comments = $project->comments()
            ->with(['user.preference', 'attachments'])
            ->withCount('likes')
            ->withExists(['likes as liked_by_me' => fn($q) => $q->where('user_id', $authId)])
            ->get();

        return response()->json([
            'data' => CommentResource::collection($comments),
        ]);
    }

    /**
     * POST /api/projects/{project}/comments
     */
    public function store(Request $request, Project $project): JsonResponse {
        $request->validate([
            'body' => 'required|string',
        ]);

        // morphMany auto-sets commentable_type + commentable_id
        $comment = $project->comments()->create([
            'project_id' => $project->id,
            'user_id'    => auth()->id(),
            'body'       => $request->body,
        ]);

        $comment->loadMissing(['user.preference', 'attachments']);
        $comment->likes_count = 0;
        $comment->liked_by_me = false;

        return response()->json(['data' => new CommentResource($comment)], 201);
    }

    /**
     * PATCH /api/projects/{project}/comments/{comment}
     */
    public function update(Request $request, Project $project, Comment $comment): JsonResponse {
        abort_if($comment->project_id !== $project->id, 404);
        abort_if($comment->commentable_type !== Project::class || $comment->commentable_id !== $project->id, 404);
        abort_if($comment->user_id !== auth()->id(), 403, 'You can only edit your own comments.');

        $request->validate([
            'body' => 'required|string',
        ]);

        $comment->update([
            'body'      => $request->body,
            'is_edited' => true,
        ]);

        $authId = auth()->id();

        $comment->load(['user.preference', 'attachments']);
        $comment->loadCount('likes');
        $comment->liked_by_me = $comment->likes()->where('user_id', $authId)->exists();

        return response()->json(['data' => new CommentResource($comment)]);
    }

    /**
     * DELETE /api/projects/{project}/comments/{comment}
     */
    public function destroy(Project $project, Comment $comment): JsonResponse {
        abort_if($comment->project_id !== $project->id, 404);
        abort_if($comment->commentable_type !== Project::class || $comment->commentable_id !== $project->id, 404);

        $isOwn = $comment->user_id === auth()->id();
        abort_if(! $isOwn && ! auth()->user()->isSuperAdmin(), 403, 'You can only delete your own comments.');

        $comment->delete();

        return response()->json(['message' => 'Comment deleted.']);
    }

    /**
     * POST /api/projects/{project}/comments/{comment}/like
     */
    public function toggleLike(Project $project, Comment $comment): JsonResponse {
        abort_if($comment->project_id !== $project->id, 404);
        abort_if($comment->commentable_type !== Project::class || $comment->commentable_id !== $project->id, 404);

        $authId = auth()->id();

        // lockForUpdate inside a transaction prevents two concurrent toggle
        // requests from both creating a like (or both deleting one).
        $liked = DB::transaction(function () use ($comment, $authId) {
            $existing = CommentLike::where('comment_id', $comment->id)
                ->where('user_id', $authId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $existing->delete();
                return false;
            }

            CommentLike::create(['comment_id' => $comment->id, 'user_id' => $authId]);
            return true;
        });

        return response()->json([
            'liked'       => $liked,
            'likes_count' => $comment->likes()->count(),
        ]);
    }
}
