<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttachmentShare;
use App\Services\AttachmentStorageService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Public pathway for shared attachments. No auth middleware — access is
 * gated purely by the token's validity (enabled + not expired) as judged
 * by AttachmentShare::isActive(). Runs in the web middleware group so
 * tokens work in a plain browser address bar, email links, etc.
 */
class PublicShareController extends Controller {

    public function __construct(private readonly AttachmentStorageService $storage) {}

    public function show(Request $request, string $token): StreamedResponse {
        $share = AttachmentShare::where('token', $token)
            ->with('attachment')
            ->firstOrFail();

        // 410 Gone is the right status for a resource that existed but is
        // now intentionally unavailable (disabled or expired). Browsers
        // and caches treat it differently from 404 (not found).
        abort_unless($share->isActive(), 410, 'This link is no longer available.');

        // ?download=1 forces the save-as dialog, but only if the share
        // owner left download enabled. Inline otherwise (images preview,
        // PDFs open in the browser viewer, etc.).
        $asDownload = $share->allow_download && $request->boolean('download');

        // Lightweight trust-signal analytics for the share owner.
        $share->increment('access_count');
        $share->update(['last_accessed_at' => now()]);

        return $this->storage->streamResponse($share->attachment, $asDownload);
    }
}
