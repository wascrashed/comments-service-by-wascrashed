<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'post_id' => $this->post_id,
            'replyto_id' => $this->replyto_id,
            'author_id' => $this->author_id,
            'number' => $this->number,
            'path' => $this->path,
            'level' => $this->level,
            'status' => $this->status,
            'body' => $this->body,
            'children_count' => $this->children_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        if (isset($this->children) && is_array($this->children)) {
            $data['children'] = array_map(fn($child) => (new self($child))->toArray($request), $this->children);
        } else {
            $data['children'] = [];
        }

        return $data;
    }
}
