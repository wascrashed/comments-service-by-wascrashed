<?php

namespace App\Repositories;

interface CommentRepositoryInterface
{
    public function fetchRootCommentsForPost(int $postId, int $page, int $perPage): array;
    public function countRootCommentsForPost(int $postId): int;
    public function fetchDescendantsForRootPaths(int $postId, array $rootPaths): array;
    public function createComment(int $postId, int $authorId, array $payload): array;
    public function findComment(int $commentId): array;
    public function updateComment(int $commentId, array $payload): array;
    public function deleteComment(int $commentId): void;
}
