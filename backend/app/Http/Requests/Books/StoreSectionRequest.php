<?php

namespace App\Http\Requests\Books;

use Illuminate\Foundation\Http\FormRequest;

class StoreSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chapter_id'     => ['sometimes', 'nullable', 'uuid', 'exists:chapters,id'],
            'section_number' => ['required', 'string', 'regex:/^\d+(\.\d+)*$/'],
            'title'          => ['required', 'string', 'min:1', 'max:255'],
            'order'          => ['sometimes', 'nullable', 'integer', 'min:0'],
            'raw_text'       => ['nullable', 'string'],
        ];
    }
}
