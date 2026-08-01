<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token'    => ['required', 'string'],
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:8', 'regex:/^(?=.*[a-zA-Z])(?=.*\d).+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.regex' => 'The password must contain at least one letter and one number.',
        ];
    }
}
