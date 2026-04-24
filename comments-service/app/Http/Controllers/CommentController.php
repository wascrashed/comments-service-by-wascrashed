<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function __construct(private CommentService $comments)
    {
    }

    public function index(Request $request, int $post): JsonResponse
    {
        $comments = $this->comments->getTreeForPost(
            $post,
            $request->query('page', 1),
            $request->query('per_page', 20),
            $request->query('expand', false)
        );

        return response()->json($comments);
    }

    public function store(StoreCommentRequest $request, int $post): JsonResponse
    {
        $comment = $this->comments->createComment(
            $post,
            $request->user()->id,
            $request->validated()
        );

        return response()->json($comment, 201);
    }

    public function show(int $comment): JsonResponse
    {
        $comment = $this->comments->getComment($comment);

        return response()->json($comment);
    }

    public function update(UpdateCommentRequest $request, int $comment): JsonResponse
    {
        $updated = $this->comments->updateComment($comment, $request->validated());

        return response()->json($updated);
    }

    public function destroy(int $comment): JsonResponse
    {
        $this->comments->deleteComment($comment);

        return response()->json(null, 204);
    }
}
