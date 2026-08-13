<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'chapter_id'   => $this->chapter_id,
            'section_number' => $this->section_number,
            'title'        => $this->title,
            'order'        => $this->order,
            'raw_text'     => $this->raw_text,
            'created_at'   => $this->created_at?->toISOString(),
        ];
    }
}