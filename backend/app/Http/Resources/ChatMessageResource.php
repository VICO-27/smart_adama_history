<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'role'       => $this->role,
            'content'    => $this->content,
            'created_at' => $this->created_at?->toISOString(),
            'sources'    => $this->whenLoaded('sources', function () {
                return $this->sources->map(fn ($source) => [
                    'id'              => $source->id,
                    'chunk_id'        => $source->content_chunk_id,
                    'similarity_score' => $source->similarity_score,
                    'chapter_title'   => $source->chunk?->section?->chapter?->title,
                    'section_title'   => $source->chunk?->section?->title,
                    'excerpt'         => mb_substr($source->chunk?->chunk_text ?? '', 0, 200),
                ]);
            }),
        ];
    }
}
