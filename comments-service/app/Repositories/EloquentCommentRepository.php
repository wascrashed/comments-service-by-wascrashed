<?php

namespace App\Repositories;

use App\Models\Comment;
use Illuminate\Database\QueryException;

class EloquentCommentRepository implements CommentRepositoryInterface
{
    public function fetchRootCommentsForPost(int $postId, int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;

        return Comment::where('post_id', $postId)
            ->whereNull('replyto_id')
            ->orderBy('path')
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(fn(Comment $comment) => $comment->toArray())
            ->all();
    }

    public function countRootCommentsForPost(int $postId): int
    {
        return Comment::where('post_id', $postId)
            ->whereNull('replyto_id')
            ->count();
    }

    public function fetchDescendantsForRootPaths(int $postId, array $rootPaths): array
    {
        if (empty($rootPaths)) {
            return [];
        }

        return Comment::where('post_id', $postId)
            ->where(function ($query) use ($rootPaths) {
                foreach ($rootPaths as $path) {
                    $query->orWhere('path', 'like', $path . '.%');
                }
            })
            ->orderBy('path')
            ->get()
            ->map(fn(Comment $comment) => $comment->toArray())
            ->all();
    }

    public function createComment(int $postId, int $authorId, array $payload): array
    {
        $parentId = $payload['replyto_id'] ?? null;
        $maxRetries = 3;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
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

                    $childIndex = $parent->children_count + 1;
                    $comment->level = $parent->level + 1;
                    $comment->path = sprintf('%s.%d', $parent->path, $childIndex);
                    $parent->increment('children_count');
                } else {
                    $comment->level = 1;
                    $comment->path = (string) $nextNumber;
                }

                $comment->save();

                return $comment->toArray();
            } catch (QueryException $exception) {
                if ($exception->getCode() === '23000' && $attempt < $maxRetries - 1) {
                    $attempt++;
                    continue;
                }

                throw $exception;
            }
        }

        throw new \Exception('Failed to create comment after retries');
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

        if ($comment->replyto_id) {
            Comment::where('id', $comment->replyto_id)->decrement('children_count');
        }

        $comment->delete();
    }
}
