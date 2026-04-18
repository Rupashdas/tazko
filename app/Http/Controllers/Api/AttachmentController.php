<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use App\Models\Project;
use App\Services\AttachmentStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller {

    public function __construct(private readonly AttachmentStorageService $storage) {}

    /**
     * Upload a new attachment in the context of a project. Creates an
     * orphan row (committed_at=null) that gets linked to a parent on
     * the next save of the RTE content referencing it.
     *
     * Auth: route middleware `can:upload-attachment,project`.
     */
    public function store(StoreAttachmentRequest $request, Project $project): AttachmentResource {
        $attachment = $this->storage->store(
            $request->file('file'),
            $project,
            $request->user(),
        );

        return (new AttachmentResource($attachment))->additional(['status' => 'created']);
    }

    /**
     * Files tab — all committed attachments for a project, newest first.
     * Supports filters via query string: file_type, attachable_type,
     * uploaded_by, q (name search).
     */
    public function index(Request $request, Project $project): \Illuminate\Http\Resources\Json\AnonymousResourceCollection {
        $this->authorize('viewAny', [Attachment::class, $project]);

        $query = Attachment::query()
            ->where('project_id', $project->id)
            ->committed()
            ->with(['uploader:id,name,avatar', 'attachable'])
            ->orderByDesc('created_at');

        if ($type = $request->string('file_type')->trim()->value()) {
            $query->where('file_type', $type);
        }
        if ($attachable = $request->string('attachable_type')->trim()->value()) {
            $query->where('attachable_type', $attachable);
        }
        if ($uploader = $request->integer('uploaded_by')) {
            $query->where('uploaded_by', $uploader);
        }
        if ($q = $request->string('q')->trim()->value()) {
            $escaped = addcslashes($q, '%_\\');
            $query->where('name', 'like', "%{$escaped}%");
        }

        return AttachmentResource::collection(
            $query->paginate($request->integer('per_page', 30))
        );
    }

    /**
     * The URL embedded in RTE content. Every image view / file download
     * through the app hits this, so the policy check must be cheap.
     * AttachmentPolicy@view verifies project membership (one indexed query).
     */
    public function stream(Request $request, Attachment $attachment): StreamedResponse {
        $this->authorize('view', $attachment);
        $asDownload = $request->boolean('download');
        return $this->storage->streamResponse($attachment, $asDownload);
    }

    /**
     * Explicit delete, typically from the Files tab. Bytes are removed
     * via the Attachment model's `deleting` event; the DB row goes in the
     * same transaction. Also cascades to any attachment_shares rows.
     */
    public function destroy(Attachment $attachment): JsonResponse {
        $this->authorize('delete', $attachment);
        $attachment->delete();
        return response()->json(null, 204);
    }
}
