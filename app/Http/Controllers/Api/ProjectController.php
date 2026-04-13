<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ProjectController extends Controller implements HasMiddleware {
    public static function middleware(): array {
        return [
            new Middleware('capability:projects.view', only: ['index']),
        ];
    }

    public function index(Request $request): JsonResponse {
        $user = $request->user();

        // ── Base query (shared filters) ───────────────────────────────────────
        $baseQuery = Project::query()
            ->where('is_archived', false);

        // ── Role-based scoping ────────────────────────────────────────────────
        if (! $user->isSuperAdmin()) {
            $baseQuery->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                    ->orWhereHas('members', fn($m) => $m->where('user_id', $user->id));
            });
        }

        // ── Search ────────────────────────────────────────────────────────────
        if ($search = $request->string('search')->trim()->value()) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // ── Status filter ─────────────────────────────────────────────────────
        if ($status = $request->input('status')) {
            $baseQuery->where('status', $status);
        }

        // ── Priority filter ───────────────────────────────────────────────────
        if ($priority = $request->input('priority')) {
            $baseQuery->where('priority', $priority);
        }

        // ── Aggregates (separate safe query) ──────────────────────────────────
        $aggregates = (clone $baseQuery)
            ->toBase()
            ->selectRaw('
                SUM(status = "In Progress") as active_count,
                SUM(status = "Completed") as completed_count,
                ROUND(AVG(progress), 0) as avg_progress
            ')
            ->first();

        // ── Pagination query ────────────────────────────────────────────────
        $perPage = (int) $request->input('per_page', 6);

        $projects = $baseQuery
            ->with(['createdBy', 'members'])
            ->withCount([
                'tasks as tasks_total',
                'tasks as tasks_done' => fn($q) => $q->where('status', 'Done'),
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => ProjectResource::collection($projects),
            'meta' => [
                'current_page'    => $projects->currentPage(),
                'last_page'       => $projects->lastPage(),
                'per_page'        => $projects->perPage(),
                'total'           => $projects->total(),
                'has_more'        => $projects->hasMorePages(),
                'active_count'    => (int) ($aggregates->active_count ?? 0),
                'completed_count' => (int) ($aggregates->completed_count ?? 0),
                'avg_progress'    => (int) ($aggregates->avg_progress ?? 0),
            ],
        ]);
    }
}
