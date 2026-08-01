<?php

namespace App\Http\Requests\Books;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // EnsureAdmin middleware handles authorization
    }

    public function rules(): array
    {
        return [
            'title'    => ['required', 'string', 'min:2', 'max:255'],
            'manuscript' => [
                'nullable',
                'file',
                'mimes:pdf,docx,md,txt',
                'max:51200', // 50 MB
            ],
        ];
    }
}
