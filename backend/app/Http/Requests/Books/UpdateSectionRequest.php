<?php

namespace App\Http\Requests\Books;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'    => ['sometimes', 'string', 'min:1', 'max:255'],
            'order'    => ['sometimes', 'integer', 'min:0'],
            'raw_text' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
