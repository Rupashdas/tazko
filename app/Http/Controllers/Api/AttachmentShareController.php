<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttachmentShareRequest;
use App\Http\Requests\UpdateAttachmentShareRequest;
use App\Http\Resources\AttachmentShareResource;
use App\Models\Attachment;
use App\Models\AttachmentShare;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AttachmentShareController extends Controller {

    /**
     * Mint a new public share link for an attachment. Token is generated
     * automatically by the AttachmentShare model's `creating` event.
     */
    public function store(StoreAttachmentShareRequest $request, Attachment $attachment): AttachmentShareResource {
        $this->authorize('share', $attachment);

        $share = $attachment->shares()->create([
            'created_by'     => $request->user()->id,
            'enabled'        => true,
            'allow_download' => $request->boolean('allow_download', true),
            'expires_at'     => $request->input('expires_at'),
        ]);

        return new AttachmentShareResource($share->load('creator:id,name,avatar'));
    }

    /**
     * List every share for one attachment — powers the "who has links to
     * this file?" panel in the UI.
     */
    public function index(Attachment $attachment): AnonymousResourceCollection {
        $this->authorize('share', $attachment);

        return AttachmentShareResource::collection(
            $attachment->shares()->with('creator:id,name,avatar')->latest()->get()
        );
    }

    /**
     * Toggle enabled, change expiry, flip allow_download. Only the share
     * creator and the project creator (via AttachmentSharePolicy@manage)
     * can edit.
     */
    public function update(UpdateAttachmentShareRequest $request, AttachmentShare $share): AttachmentShareResource {
        $this->authorize('manage', $share);
        $share->fill($request->validated())->save();
        return new AttachmentShareResource($share->fresh('creator:id,name,avatar'));
    }

    /**
     * Revoke (hard delete). The token is destroyed — even if someone
     * recreates a share for the same attachment, the new token is
     * different, so previously-distributed URLs are permanently dead.
     */
    public function destroy(AttachmentShare $share): JsonResponse {
        $this->authorize('manage', $share);
        $share->delete();
        return response()->json(null, 204);
    }
}
