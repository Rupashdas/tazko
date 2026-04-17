<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentShareRequest extends FormRequest {

    public function authorize(): bool {
        // AttachmentPolicy@share runs in the controller before reaching here.
        return true;
    }

    public function rules(): array {
        return [
            'expires_at'     => ['nullable', 'date', 'after:now'],
            'allow_download' => ['sometimes', 'boolean'],
        ];
    }
}
