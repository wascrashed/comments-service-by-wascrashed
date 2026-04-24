<?php

namespace App\Repositories;

use App\Models\Comment;
use Illuminate\Database\QueryException;

class EloquentCommentRepository implements CommentRepositoryInterface
{
    public function fetchCommentsForPost(int $postId): array
    {
        return Comment::where('post_id', $postId)
            ->orderBy('path')
            ->get()
            ->map(fn(Comment $comment) => $comment->toArray())
            ->all();
    }

    public function createComment(int $postId, int $authorId, array $payload): array
    {
        $parentId = $payload['replyto_id'] ?? null;
        $comment = new Comment();
        $comment->post_id = $postId;
        $comment->replyto_id = $parentId;
        $comment->author_id = $authorId;
        $comment->body = $payload['body'];
        $comment->status = $payload['status'] ?? 'published';

        if ($parentId) {
            $parent = Comment::findOrFail($parentId);
            $comment->level = $parent->level + 1;
            $comment->path = $parent->path . '.' . ($parent->children_count + 1);
        } else {
            $comment->level = 1;
            $comment->path = (Comment::where('post_id', $postId)->max('id') ?? 0) + 1;
        }

        try {
            $comment->save();
        } catch (QueryException $exception) {
            throw $exception;
        }

        return $comment->toArray();
    }

    public function findComment(int $commentId): array
    {
        return Comment::findOrFail($commentId)->toArray();
    }

    public function updateComment(int $commentId, array $payload): array
    {
        $comment = Comment::findOrFail($commentId);
        $comment->fill($payload);
        $comment->save();

        return $comment->toArray();
    }

    public function deleteComment(int $commentId): void
    {
        $comment = Comment::findOrFail($commentId);
        $comment->delete();
    }
}
