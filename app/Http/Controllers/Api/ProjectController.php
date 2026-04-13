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

    /**
     * GET /api/projects
     *
     * Query params:
     *   search   — partial match on name or description
     *   status   — exact match: Planning | In Progress | On Hold | Completed
     *   priority — exact match: Low | Medium | High | Urgent
     *   per_page — default 6
     */
    public function index(Request $request): JsonResponse {
        $user = $request->user();

        $query = Project::with(['createdBy', 'members'])
            ->where('is_archived', false);

        // ── Role-based scoping ────────────────────────────────────────────────
        // Super-admin sees every project.
        // Everyone else sees only projects they created or are a member of.
        if (! $user->isSuperAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhereHas('members', fn($m) => $m->where('user_id', $user->id));
            });
        }

        // ── Search ────────────────────────────────────────────────────────────
        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // ── Status filter ─────────────────────────────────────────────────────
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // ── Priority filter ───────────────────────────────────────────────────
        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        // ── Pagination ────────────────────────────────────────────────────────
        $perPage = (int) $request->input('per_page', 6);
        $projects = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'data' => ProjectResource::collection($projects),
            'meta' => [
                'current_page' => $projects->currentPage(),
                'last_page'    => $projects->lastPage(),
                'per_page'     => $projects->perPage(),
                'total'        => $projects->total(),
                'has_more'     => $projects->hasMorePages(),
            ],
        ]);
    }
}
