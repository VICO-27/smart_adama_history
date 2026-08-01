<?php

namespace App\Http\Requests\Books;

use Illuminate\Foundation\Http\FormRequest;

class StoreChapterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:1', 'max:255'],
            'order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
