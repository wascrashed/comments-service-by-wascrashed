<?php

namespace App\Services;

use App\Domain\Comment\TreeBuilder;
use App\Repositories\CommentRepositoryInterface;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\DatabaseManager;

class CommentService
{
    private const CACHE_TTL = 300;

    public function __construct(
        private CommentRepositoryInterface $comments,
        private TreeBuilder $treeBuilder,
        private DatabaseManager $database,
        private Cache $cache
    ) {
    }

    public function getTreeForPost(int $postId, int $page, int $perPage, bool $expand): array
    {
        $cacheKey = $this->buildCacheKey($postId, $page, $perPage, $expand);

        return $this->cache->remember($cacheKey, self::CACHE_TTL, function () use ($postId, $page, $perPage, $expand) {
            $rootComments = $this->comments->fetchRootCommentsForPost($postId, $page, $perPage);
            $totalRoots = $this->comments->countRootCommentsForPost($postId);
            $children = $expand ? $this->comments->fetchDescendantsForRootPaths($postId, array_column($rootComments, 'path')) : [];

            return $this->treeBuilder->buildTree($rootComments, $children, $page, $perPage, $expand, $totalRoots);
        });
    }

    public function createComment(int $postId, int $authorId, array $payload): array
    {
        $result = $this->database->transaction(function () use ($postId, $authorId, $payload) {
            return $this->comments->createComment($postId, $authorId, $payload);
        });

        $this->incrementTreeVersion($postId);

        return $result;
    }

    public function getComment(int $commentId): array
    {
        return $this->comments->findComment($commentId);
    }

    public function updateComment(int $commentId, array $payload): array
    {
        $comment = $this->comments->findComment($commentId);

        $result = $this->database->transaction(function () use ($commentId, $payload) {
            return $this->comments->updateComment($commentId, $payload);
        });

        $this->incrementTreeVersion($comment['post_id']);

        return $result;
    }

    public function deleteComment(int $commentId): void
    {
        $comment = $this->comments->findComment($commentId);

        $this->database->transaction(function () use ($commentId) {
            $this->comments->deleteComment($commentId);
        });

        $this->incrementTreeVersion($comment['post_id']);
    }

    private function buildCacheKey(int $postId, int $page, int $perPage, bool $expand): string
    {
        $version = $this->cache->get($this->getVersionKey($postId), 1);

        return sprintf('comments:post:%d:v%d:page:%d:per_page:%d:expand:%d', $postId, $version, $page, $perPage, $expand ? 1 : 0);
    }

    private function incrementTreeVersion(int $postId): void
    {
        $key = $this->getVersionKey($postId);
        $version = $this->cache->get($key, 1);
        $this->cache->put($key, $version + 1, 86400);
    }

    private function getVersionKey(int $postId): string
    {
        return sprintf('comments:post:%d:version', $postId);
    }
}
