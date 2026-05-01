<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertDraftRequest;
use App\Models\Comment;
use App\Models\Draft;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class DraftController extends Controller {

    private const CONTEXT_KEY_REGEX = '/^(?:'
        . 'task:\d+:comment:new'
        . '|task:\d+:description'
        . '|project:\d+:comment:new'
        . '|project:\d+:task:new'
        . '|comment:\d+:edit'
        . ')$/';

    public function show(Request $request, string $contextKey) {
        $this->validateContextKey($contextKey);
        $this->authorizeContextKey($contextKey, $request);

        $draft = Draft::where('user_id', $request->user()->id)
                      ->where('context_key', $contextKey)
                      ->first();

        if (! $draft) {
            return response()->noContent(Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'content'        => $draft->content,
            'attachment_ids' => $draft->attachment_ids ?? [],
            'updated_at'     => $draft->updated_at?->toIso8601String(),
        ]);
    }

    public function update(UpsertDraftRequest $request, string $contextKey) {
        $this->validateContextKey($contextKey);
        $this->authorizeContextKey($contextKey, $request);

        $draft = Draft::firstOrNew([
            'user_id'     => $request->user()->id,
            'context_key' => $contextKey,
        ]);
        $draft->content = $request->input('content', '');
        $draft->syncAttachmentIds();
        $draft->save();

        return response()->noContent();
    }

    public function destroy(Request $request, string $contextKey) {
        $this->validateContextKey($contextKey);
        $this->authorizeContextKey($contextKey, $request);

        Draft::where('user_id', $request->user()->id)
             ->where('context_key', $contextKey)
             ->delete();

        return response()->noContent();
    }

    private function validateContextKey(string $key): void {
        if (! preg_match(self::CONTEXT_KEY_REGEX, $key)) {
            throw ValidationException::withMessages([
                'context_key' => 'Invalid draft context key.',
            ]);
        }
    }

    /**
     * Verify the current user has access to the project referenced by the
     * context key. Without this gate, any authenticated user could read or
     * write drafts for tasks/projects/comments they have no rights to see.
     */
    private function authorizeContextKey(string $key, Request $request): void {
        $user = $request->user();

        if (preg_match('/^project:(\d+):/', $key, $m)) {
            $project = Project::find($m[1]);
            abort_if(! $project, 404);
            $this->ensureProjectMember($user, $project);
            return;
        }

        if (preg_match('/^task:(\d+):/', $key, $m)) {
            $task = Task::find($m[1]);
            abort_if(! $task, 404);
            abort_if(! $task->project, 404);
            $this->ensureProjectMember($user, $task->project);
            return;
        }

        if (preg_match('/^comment:(\d+):edit$/', $key, $m)) {
            $comment = Comment::find($m[1]);
            abort_if(! $comment, 404);
            abort_if($comment->user_id !== $user->id, 403);
            $project = Project::find($comment->project_id);
            abort_if(! $project, 404);
            $this->ensureProjectMember($user, $project);
            return;
        }
    }

    private function ensureProjectMember($user, Project $project): void {
        if ($user->isSuperAdmin()) {
            return;
        }

        $isMember = $project->created_by === $user->id
            || $project->members()->where('user_id', $user->id)->exists();
        abort_if(! $isMember, 403, 'You do not have access to this resource.');
    }
}
