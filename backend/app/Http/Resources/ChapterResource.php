<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChapterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'book_id'          => $this->book_id,
            'title'            => $this->title,
            'order'            => $this->order,
            'ingestion_status' => $this->ingestion_status,
            'ingested_at'      => $this->ingested_at?->toISOString(),
            'created_at'       => $this->created_at?->toISOString(),
            // Conditionally include sections when loaded
            'sections'         => SectionResource::collection($this->whenLoaded('sections')),
        ];
    }
}
