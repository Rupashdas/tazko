<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Label;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LabelController extends Controller implements HasMiddleware {
    public static function middleware(): array {
        return [
            new Middleware('project.member'),
            // Viewing the label palette is part of viewing tasks.
            new Middleware('capability:tasks.view',   only: ['index']),
            // Creating/removing labels is a task-update action.
            new Middleware('capability:tasks.update', only: ['store', 'destroy']),
        ];
    }

    /**
     * GET /api/projects/{project}/labels
     */
    public function index(Project $project): JsonResponse {
        $labels = $project->labels()->orderBy('name')->get();

        return response()->json([
            'data' => $labels->map(fn($l) => [
                'id'    => $l->id,
                'name'  => $l->name,
                'color' => $l->color,
            ]),
        ]);
    }

    /**
     * POST /api/projects/{project}/labels
     */
    public function store(Request $request, Project $project): JsonResponse {
        $request->validate([
            'name'  => 'required|string|max:50',
            'color' => 'nullable|string|max:255',
        ]);

        $label = $project->labels()->create([
            'name'  => $request->name,
            'color' => $request->color ?? 'bg-slate-500/15 text-slate-600 border-slate-200',
        ]);

        return response()->json([
            'data' => [
                'id'    => $label->id,
                'name'  => $label->name,
                'color' => $label->color,
            ],
        ], 201);
    }

    /**
     * DELETE /api/projects/{project}/labels/{label}
     */
    public function destroy(Project $project, Label $label): JsonResponse {
        abort_if($label->project_id !== $project->id, 404);

        $label->delete();

        return response()->json(['message' => 'Label deleted.']);
    }
}
