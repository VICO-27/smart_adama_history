<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'last_activity_at' => $this->last_activity_at?->toISOString(),
            'created_at'       => $this->created_at?->toISOString(),
            'messages'         => ChatMessageResource::collection($this->whenLoaded('messages')),
        ];
    }
}
