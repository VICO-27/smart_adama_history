<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'min:2', 'max:100'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            // Min 8 chars, at least 1 letter and 1 number (Req 1.1)
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
