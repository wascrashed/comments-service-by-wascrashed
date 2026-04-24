<?php

namespace App\Repositories;

interface CommentRepositoryInterface
{
    public function fetchCommentsForPost(int $postId): array;
    public function createComment(int $postId, int $authorId, array $payload): array;
    public function findComment(int $commentId): array;
    public function updateComment(int $commentId, array $payload): array;
    public function deleteComment(int $commentId): void;
}
