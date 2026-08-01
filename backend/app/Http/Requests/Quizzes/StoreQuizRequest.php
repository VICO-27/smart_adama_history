<?php

namespace App\Http\Requests\Quizzes;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuizRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'             => ['required', 'string', 'min:2', 'max:255'],
            'passing_score_pct' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
