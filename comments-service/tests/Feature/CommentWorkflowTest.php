<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_and_fetch_comment_tree(): void
    {
        $user = User::factory()->create();
        $postId = 1;

        $this->actingAs($user)
            ->postJson("/api/posts/{$postId}/comments", [
                'body' => 'Test comment',
            ])
            ->assertStatus(201)
            ->assertJsonPath('body', 'Test comment');

        $this->getJson("/api/posts/{$postId}/comments")
            ->assertStatus(200)
            ->assertJsonStructure(['*' => ['id', 'body', 'children']]);
    }
}
