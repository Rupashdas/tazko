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

class CommentController extends Controller implements HasMiddleware {
    public static function middleware(): array {
        return [
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
            ->with(['user', 'attachments'])
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

        $comment->loadMissing(['user', 'attachments']);
        $comment->likes_count = 0;
        $comment->liked_by_me = false;

        return response()->json(['data' => new CommentResource($comment)], 201);
    }

    /**
     * PATCH /api/projects/{project}/comments/{comment}
     */
    public function update(Request $request, Project $project, Comment $comment): JsonResponse {
        abort_if($comment->project_id !== $project->id, 404);
        abort_if($comment->user_id !== auth()->id(), 403, 'You can only edit your own comments.');

        $request->validate([
            'body' => 'required|string',
        ]);

        $comment->update([
            'body'      => $request->body,
            'is_edited' => true,
        ]);

        $comment->loadMissing(['user', 'attachments']);
        $authId = auth()->id();
        $comment->likes_count = $comment->likes()->count();
        $comment->liked_by_me = $comment->likes()->where('user_id', $authId)->exists();

        return response()->json(['data' => new CommentResource($comment)]);
    }

    /**
     * DELETE /api/projects/{project}/comments/{comment}
     */
    public function destroy(Project $project, Comment $comment): JsonResponse {
        abort_if($comment->project_id !== $project->id, 404);

        // Allow own comment deletion or if the user has delete capability (already gated by middleware)
        if ($comment->user_id !== auth()->id()) {
            // Only project owners/admins with the capability can delete others' comments
            // The middleware already ensures `comments.delete` cap — allow it
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted.']);
    }

    /**
     * POST /api/projects/{project}/comments/{comment}/like
     */
    public function toggleLike(Project $project, Comment $comment): JsonResponse {
        abort_if($comment->project_id !== $project->id, 404);

        $authId = auth()->id();
        $existing = CommentLike::where('comment_id', $comment->id)->where('user_id', $authId)->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            CommentLike::create(['comment_id' => $comment->id, 'user_id' => $authId]);
            $liked = true;
        }

        return response()->json([
            'liked'       => $liked,
            'likes_count' => $comment->likes()->count(),
        ]);
    }
}
