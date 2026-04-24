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

        $nextNumber = Comment::where('post_id', $postId)
            ->lockForUpdate()
            ->max('number');

        $nextNumber = ($nextNumber ?? 0) + 1;

        $comment = new Comment();
        $comment->post_id = $postId;
        $comment->replyto_id = $parentId;
        $comment->author_id = $authorId;
        $comment->body = $payload['body'];
        $comment->status = $payload['status'] ?? 'published';
        $comment->number = $nextNumber;

        if ($parentId) {
            $parent = Comment::where('id', $parentId)
                ->lockForUpdate()
                ->firstOrFail();

            $comment->level = $parent->level + 1;
            $comment->path = sprintf('%s.%d', $parent->path, $nextNumber);
            $parent->increment('children_count');
        } else {
            $comment->level = 1;
            $comment->path = (string)$nextNumber;
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
