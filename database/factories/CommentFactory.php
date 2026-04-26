<?php

namespace Database\Factories;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'post_id' => $this->faker->numberBetween(1, 100),
            'replyto_id' => null,
            'author_id' => $this->faker->numberBetween(1, 1000),
            'number' => $this->faker->unique()->numberBetween(1, 10000),
            'path' => $this->faker->unique()->word,
            'level' => 1,
            'status' => 'published',
            'body' => $this->faker->sentence,
            'children_count' => 0,
        ];
    }

    public function replyTo(Comment $parent): self
    {
        return $this->state(function (array $attributes) use ($parent) {
            return [
                'replyto_id' => $parent->id,
                'level' => $parent->level + 1,
                'path' => $parent->path . '.' . $this->faker->numberBetween(1, 100),
            ];
        });
    }
}