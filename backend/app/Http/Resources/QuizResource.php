<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizResource extends JsonResource
{
    /** @param  bool  $includeAnswers  Set true only for admin views */
    public function __construct($resource, private bool $includeAnswers = false)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'chapter_id'        => $this->chapter_id,
            'title'             => $this->title,
            'passing_score_pct' => $this->passing_score_pct,
            'status'            => $this->status,
            'questions'         => $this->whenLoaded('questions', function () {
                return $this->questions->map(fn ($q) => [
                    'id'            => $q->id,
                    'question_text' => $q->question_text,
                    'type'          => $q->type,
                    'order'         => $q->order,
                    'options'       => $q->options->map(fn ($o) => array_filter([
                        'id'          => $o->id,
                        'option_text' => $o->option_text,
                        'order'       => $o->order,
                        // Only expose is_correct in admin context (Req 9.1)
                        'is_correct'  => $this->includeAnswers ? $o->is_correct : null,
                    ], fn ($v) => $v !== null)),
                ]);
            }),
        ];
    }
}
