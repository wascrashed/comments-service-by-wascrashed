<?php

namespace App\Services;

use App\Domain\Comment\TreeBuilder;
use App\Repositories\CommentRepositoryInterface;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\DatabaseManager;

class CommentService
{
    public function __construct(
        private CommentRepositoryInterface $comments,
        private TreeBuilder $treeBuilder,
        private DatabaseManager $database,
        private Cache $cache
    ) {
    }

    public function getTreeForPost(int $postId, int $page, int $perPage, bool $expand): array
    {
        $cacheKey = "comments:post:{$postId}:page:{$page}:per_page:{$perPage}:expand:" . ($expand ? '1' : '0');

        return $this->cache->remember($cacheKey, 300, function () use ($postId, $page, $perPage, $expand) {
            $comments = $this->comments->fetchCommentsForPost($postId);
            return $this->treeBuilder->buildTree($comments, $page, $perPage, $expand);
        });
    }

    public function createComment(int $postId, int $authorId, array $payload): array
    {
        $result = $this->database->transaction(function () use ($postId, $authorId, $payload) {
            return $this->comments->createComment($postId, $authorId, $payload);
        });

        // Invalidate cache for this post
        $this->cache->forget("comments:post:{$postId}:*");

        return $result;
    }

    public function getComment(int $commentId): array
    {
        return $this->comments->findComment($commentId);
    }

    public function updateComment(int $commentId, array $payload): array
    {
        $result = $this->database->transaction(function () use ($commentId, $payload) {
            return $this->comments->updateComment($commentId, $payload);
        });

        // Invalidate cache for the post of this comment
        $comment = $this->comments->findComment($commentId);
        $this->cache->forget("comments:post:{$comment['post_id']}:*");

        return $result;
    }

    public function deleteComment(int $commentId): void
    {
        $comment = $this->comments->findComment($commentId);

        $this->database->transaction(function () use ($commentId) {
            $this->comments->deleteComment($commentId);
        });

        // Invalidate cache for the post
        $this->cache->forget("comments:post:{$comment['post_id']}:*");
    }
}
