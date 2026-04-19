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
            new Middleware('project.member',              only: ['update', 'destroy', 'archive', 'restore']),
            new Middleware('project.not_archived',        only: ['update', 'archive']),
            new Middleware('capability:projects.view',    only: ['index', 'show', 'archivedIndex']),
            new Middleware('capability:projects.create',  only: ['store']),
            new Middleware('capability:projects.update',  only: ['update']),
            new Middleware('capability:projects.delete',  only: ['destroy']),
            new Middleware('capability:projects.archive', only: ['archive']),
            new Middleware('capability:projects.restore', only: ['restore']),
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
                $q->where('created_by', $user->id)->orWhereHas('members', fn($m) => $m->where('user_id', $user->id));
            });
        }

        // ── Search ────────────────────────────────────────────────────────────
        if ($search = $request->string('search')->trim()->value()) {
            $escaped = addcslashes($search, '%_\\');
            $baseQuery->where(function ($q) use ($escaped) {
                $q->where('name', 'like', "%{$escaped}%")
                    ->orWhere('description', 'like', "%{$escaped}%");
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
            ->with(['createdBy', 'members.preference'])
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

    /**
     * GET /api/projects/{project}
     */
    public function show(Request $request, Project $project): JsonResponse {
        $user = $request->user();

        if (! $user->isSuperAdmin()) {
            $allowed = $project->created_by === $user->id
                || $project->members()->where('user_id', $user->id)->exists();

            if (! $allowed) {
                return response()->json(['message' => 'You do not have access to this project.'], 403);
            }
        }

        $project->load(['createdBy', 'members']);
        $project->loadCount([
            'tasks as tasks_total',
            'tasks as tasks_done' => fn($q) => $q->where('status', 'Done'),
        ]);

        return response()->json([
            'data' => new ProjectResource($project),
        ]);
    }

    /**
     * POST /api/projects
     */
    public function store(Request $request): JsonResponse {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'goal'        => 'nullable|string|max:500',
            'status'      => 'nullable|in:Planning,In Progress,On Hold,Completed',
            'priority'    => 'nullable|in:Low,Medium,High,Urgent',
            'color'       => 'nullable|string|max:50',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
        ]);

        $project = Project::create([
            ...$validated,
            'created_by' => $request->user()->id,
            'progress'   => 0,
        ]);

        // Automatically add the creator as a project member with role 'owner'
        $project->members()->attach($request->user()->id, ['role' => 'owner']);

        $project->load(['createdBy', 'members']);
        $project->loadCount([
            'tasks as tasks_total',
            'tasks as tasks_done' => fn($q) => $q->where('status', 'Done'),
        ]);

        return response()->json([
            'data'    => new ProjectResource($project),
            'message' => 'Project created successfully.',
        ], 201);
    }

    /**
     * PUT /api/projects/{project}
     */
    public function update(Request $request, Project $project): JsonResponse {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'goal'        => 'nullable|string|max:500',
            'status'      => 'nullable|in:Planning,In Progress,On Hold,Completed',
            'priority'    => 'nullable|in:Low,Medium,High,Urgent',
            'color'       => 'nullable|string|max:50',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
        ]);

        $project->update($validated);

        $project->load(['createdBy', 'members']);
        $project->loadCount([
            'tasks as tasks_total',
            'tasks as tasks_done' => fn($q) => $q->where('status', 'Done'),
        ]);

        return response()->json([
            'data'    => new ProjectResource($project),
            'message' => 'Project updated successfully.',
        ]);
    }

    /**
     * DELETE /api/projects/{project}
     */
    public function destroy(Project $project): JsonResponse {
        $project->delete();

        return response()->json([
            'message' => 'Project deleted successfully.',
        ]);
    }

    /**
     * PATCH /api/projects/{project}/archive
     */
    public function archive(Project $project): JsonResponse {
        $project->update([
            'is_archived' => true,
            'archived_at' => now(),
        ]);

        return response()->json([
            'message' => 'Project archived successfully.',
        ]);
    }

    /**
     * GET /api/projects/archived
     */
    public function archivedIndex(Request $request): JsonResponse {
        $user = $request->user();

        $query = Project::query()->where('is_archived', true);

        if (! $user->isSuperAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                    ->orWhereHas('members', fn($m) => $m->where('user_id', $user->id));
            });
        }

        if ($search = $request->string('search')->trim()->value()) {
            $escaped = addcslashes($search, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('name', 'like', "%{$escaped}%")
                    ->orWhere('description', 'like', "%{$escaped}%");
            });
        }

        // Stat counts
        $total      = (clone $query)->count();
        $completed  = (clone $query)->where('status', 'Completed')->count();
        $incomplete = $total - $completed;

        $perPage  = (int) $request->input('per_page', 6);
        $projects = $query
            ->with(['createdBy', 'members.preference'])
            ->withCount([
                'tasks as tasks_total',
                'tasks as tasks_done' => fn($q) => $q->where('status', 'Done'),
            ])
            ->orderBy('archived_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => ProjectResource::collection($projects),
            'meta' => [
                'current_page' => $projects->currentPage(),
                'last_page'    => $projects->lastPage(),
                'per_page'     => $projects->perPage(),
                'total'        => $projects->total(),
                'has_more'     => $projects->hasMorePages(),
                'completed'    => $completed,
                'incomplete'   => $incomplete,
            ],
        ]);
    }

    /**
     * PATCH /api/projects/{project}/restore
     */
    public function restore(Project $project): JsonResponse {
        $project->update([
            'is_archived' => false,
            'archived_at' => null,
        ]);

        return response()->json([
            'message' => 'Project restored successfully.',
        ]);
    }
}
