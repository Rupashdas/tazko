<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttachmentShareRequest extends FormRequest {

    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        // All fields are optional on update — the client sends only what
        // changed (flip enabled, extend expiry, toggle allow_download, …).
        return [
            'enabled'        => ['sometimes', 'boolean'],
            'expires_at'     => ['sometimes', 'nullable', 'date', 'after:now'],
            'allow_download' => ['sometimes', 'boolean'],
        ];
    }
}
