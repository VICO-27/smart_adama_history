<?php

namespace App\Http\Requests\Quizzes;

use Illuminate\Foundation\Http\FormRequest;

class SubmitQuizAttemptRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'answers'                        => ['required', 'array'],
            'answers.*.question_id'          => ['required', 'uuid'],
            'answers.*.selected_option_ids'  => ['required', 'array'],
            'answers.*.selected_option_ids.*' => ['uuid'],
        ];
    }
}
