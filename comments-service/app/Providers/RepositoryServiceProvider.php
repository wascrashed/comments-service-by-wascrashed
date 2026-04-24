<?php

namespace App\Providers;

use App\Repositories\CommentRepositoryInterface;
use App\Repositories\EloquentCommentRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CommentRepositoryInterface::class, EloquentCommentRepository::class);
    }

    public function boot(): void
    {
    }
}
