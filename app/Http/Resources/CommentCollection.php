<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CommentCollection extends ResourceCollection
{
    public function toArray($request): array
    {
        return CommentResource::collection($this->collection);
    }

    public function with($request): array
    {
        return [
            'meta' => $this->additional['meta'] ?? [],
        ];
    }
}
