<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Owns everything disk-related for attachments: validation, persistence,
 * and streaming. Does NOT know about RTE HTML or parent linking — that's
 * AttachmentSyncService's job. Does NOT own file deletion — the Attachment
 * model's `deleting` event handles that so cleanup happens uniformly for
 * direct deletes, sync-service unlinks, and the reaper.
 */
class AttachmentStorageService {

    /**
     * Validate, persist bytes to disk, and create an orphan Attachment row
     * (committed_at = null, attachable = null). The sync service will link
     * it to a parent on the next save of the containing model.
     *
     * @throws ValidationException when size, extension, or mime is rejected.
     */
    public function store(UploadedFile $file, Project $project, User $uploader): Attachment {
        $this->validate($file);

        $disk      = config('attachments.disk', 'local');
        $extension = strtolower($file->getClientOriginalExtension());
        // Year/month subfolders keep any single directory from growing
        // unbounded and make manual disk archeology easier.
        $directory = 'attachments/'.now()->format('Y').'/'.now()->format('m');
        $filename  = Str::uuid()->toString().($extension ? '.'.$extension : '');
        $path      = $directory.'/'.$filename;

        // storeAs returns false on failure; we bail loudly rather than
        // creating a DB row pointing at nothing.
        $stored = Storage::disk($disk)->putFileAs($directory, $file, $filename);
        if ($stored === false) {
            throw ValidationException::withMessages([
                'file' => 'Failed to store the uploaded file. Please try again.',
            ]);
        }

        $mime = $file->getMimeType() ?: $file->getClientMimeType();

        return Attachment::create([
            'uploaded_by' => $uploader->id,
            'project_id'  => $project->id,
            // attachable_type / attachable_id intentionally NULL — sync service
            // links these when the parent is saved.
            'name'        => $file->getClientOriginalName(),
            'path'        => $path,
            'disk'        => $disk,
            'mime_type'   => $mime,
            'file_type'   => $this->deriveFileType($mime),
            'size'        => $file->getSize(),
            // committed_at stays NULL until sync service marks it committed.
        ]);
    }

    /**
     * Build a streamed response for an attachment. Used by both the internal
     * (/api/attachments/{id}/stream) and public (/share/{token}) routes —
     * the only difference between them is which gate ran beforehand.
     *
     * @param bool $asDownload When true, forces a download dialog via
     *                         Content-Disposition: attachment. When false,
     *                         the browser may render inline (images in
     *                         <img>, PDFs in the viewer, etc.).
     */
    public function streamResponse(Attachment $attachment, bool $asDownload = false): StreamedResponse {
        $disposition = $asDownload ? 'attachment' : 'inline';
        return Storage::disk($attachment->disk)->response(
            $attachment->path,
            $attachment->name,
            ['Content-Type' => $attachment->mime_type ?: 'application/octet-stream'],
            $disposition,
        );
    }

    /*---------------------------------------------------------------------------
    | Internals
    ---------------------------------------------------------------------------*/

    private function validate(UploadedFile $file): void {
        if (! $file->isValid()) {
            throw ValidationException::withMessages([
                'file' => 'The upload was not received correctly.',
            ]);
        }

        $maxBytes = (int) config('attachments.max_size_mb', 50) * 1024 * 1024;
        if ($file->getSize() > $maxBytes) {
            $limit = config('attachments.max_size_mb', 50);
            throw ValidationException::withMessages([
                'file' => "File is larger than the {$limit} MB limit.",
            ]);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if ($extension !== '' && in_array($extension, config('attachments.denied_extensions', []), true)) {
            throw ValidationException::withMessages([
                'file' => "Files with the .{$extension} extension are not allowed.",
            ]);
        }

        // getMimeType() sniffs the file content via finfo — blocks
        // malware.exe renamed to invoice.pdf. getClientMimeType() is
        // client-declared and not trusted for security checks.
        $sniffedMime = $file->getMimeType();
        if ($sniffedMime && in_array($sniffedMime, config('attachments.denied_mime_types', []), true)) {
            throw ValidationException::withMessages([
                'file' => 'That file type is not allowed.',
            ]);
        }
    }

    private function deriveFileType(?string $mime): string {
        if (! $mime) return 'file';
        if (str_starts_with($mime, 'image/')) return 'image';
        if (str_starts_with($mime, 'video/')) return 'video';
        if (str_starts_with($mime, 'audio/')) return 'audio';
        return 'file';
    }
}
