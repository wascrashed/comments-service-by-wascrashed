<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\User;
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
            ->assertJsonPath('data.body', 'Test comment');

        $this->getJson("/api/posts/{$postId}/comments")
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['*' => ['id', 'body', 'children']]]);
    }

    public function test_can_create_reply_and_build_tree(): void
    {
        $user = User::factory()->create();
        $postId = 1;

        $parent = Comment::factory()->create(['post_id' => $postId, 'author_id' => $user->id]);

        $this->actingAs($user)
            ->postJson("/api/posts/{$postId}/comments", [
                'body' => 'Reply comment',
                'replyto_id' => $parent->id,
            ])
            ->assertStatus(201);

        $response = $this->getJson("/api/posts/{$postId}/comments?expand=true");
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.children.0.body', 'Reply comment');
    }
}
