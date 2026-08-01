<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ChapterResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'title'      => $this->title,
            'status'     => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            // Automatically include nested chapters if they are eager-loaded
            'chapters'   => ChapterResource::collection($this->whenLoaded('chapters')),
        ];
    }
}