<?php

namespace App\Services;

use App\Domain\Comment\TreeBuilder;
use App\Repositories\CommentRepositoryInterface;
use Illuminate\Database\DatabaseManager;

class CommentService
{
    public function __construct(
        private CommentRepositoryInterface $comments,
        private TreeBuilder $treeBuilder,
        private DatabaseManager $database
    ) {
    }

    public function getTreeForPost(int $postId, int $page, int $perPage, bool $expand): array
    {
        $comments = $this->comments->fetchCommentsForPost($postId);
        return $this->treeBuilder->buildTree($comments, $page, $perPage, $expand);
    }

    public function createComment(int $postId, int $authorId, array $payload): array
    {
        return $this->database->transaction(function () use ($postId, $authorId, $payload) {
            return $this->comments->createComment($postId, $authorId, $payload);
        });
    }

    public function getComment(int $commentId): array
    {
        return $this->comments->findComment($commentId);
    }

    public function updateComment(int $commentId, array $payload): array
    {
        return $this->database->transaction(function () use ($commentId, $payload) {
            return $this->comments->updateComment($commentId, $payload);
        });
    }

    public function deleteComment(int $commentId): void
    {
        $this->database->transaction(function () use ($commentId) {
            $this->comments->deleteComment($commentId);
        });
    }
}
