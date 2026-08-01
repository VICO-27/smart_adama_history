<?php

namespace App\Http\Requests\Quizzes;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuizQuestionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'question_text'          => ['required', 'string', 'min:1'],
            'type'                   => ['required', 'in:single,multiple,true_false'],
            'explanation'            => ['nullable', 'string'],
            'order'                  => ['sometimes', 'integer', 'min:0'],
            // Options array (Req 8.2: min 2 options, at least 1 correct)
            'options'                => ['required', 'array', 'min:2'],
            'options.*.option_text'  => ['required', 'string', 'min:1'],
            'options.*.is_correct'   => ['required', 'boolean'],
            'options.*.order'        => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $options = $this->input('options', []);
            $hasCorrect = collect($options)->some(fn ($o) => (bool) ($o['is_correct'] ?? false));

            if (! $hasCorrect) {
                $validator->errors()->add('options', 'At least one option must be marked as correct.');
            }
        });
    }
}
