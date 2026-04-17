<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest {

    public function authorize(): bool {
        // Route-level can:upload-attachment,project gate runs first; this
        // FormRequest is only hit for members. Return true here to defer
        // to that middleware.
        return true;
    }

    public function rules(): array {
        // Authoritative size/extension/mime validation happens in
        // AttachmentStorageService (needs byte access for mime sniff).
        // Here we just ensure a file arrived and isn't obviously huge —
        // catching oversize before it consumes a PHP request slot.
        $maxKb = (int) config('attachments.max_size_mb', 50) * 1024;

        return [
            'file' => ['required', 'file', "max:{$maxKb}"],
        ];
    }

    public function messages(): array {
        $mb = (int) config('attachments.max_size_mb', 50);
        return [
            'file.required' => 'No file was uploaded.',
            'file.file'     => 'The upload is not a valid file.',
            'file.max'      => "File is larger than the {$mb} MB limit.",
        ];
    }
}
