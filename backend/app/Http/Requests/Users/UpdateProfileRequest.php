<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'            => ['sometimes', 'string', 'min:2', 'max:100'],
            'locale'          => ['sometimes', 'string', 'size:2'],
            'notify_badges'   => ['sometimes', 'boolean'],
        ];
    }
}
