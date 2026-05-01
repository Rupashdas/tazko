<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpsertDraftRequest extends FormRequest {

    public function authorize(): bool {
        return $this->user() !== null;
    }

    public function rules(): array {
        return [
            'content' => ['present', 'string', 'max:2097152'],
        ];
    }
}
