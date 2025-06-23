<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateCommentRequest;
use App\Models\PbcComment;
use App\Models\PbcRequest;
use App\Services\PbcCommentService;
use Illuminate\Http\Request;

class PbcCommentController extends BaseController
{
    protected $pbcCommentService;

    public function __construct(PbcCommentService $pbcCommentService)
    {
        $this->pbcCommentService = $pbcCommentService;
    }

    public function index(PbcRequest $pbcRequest, Request $request)
    {
        try {
            $comments = $this->pbcCommentService->getPbcRequestComments($pbcRequest, $request->user());
            return $this->success($comments, 'Comments retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve comments', $e->getMessage(), 500);
        }
    }

    public function store(CreateCommentRequest $request)
    {
        try {
            $comment = $this->pbcCommentService->createComment($request->validated(), $request->user());
            return $this->success($comment, 'Comment created successfully', 201);
        } catch (\Exception $e) {
            return $this->error('Failed to create comment', $e->getMessage(), 500);
        }
    }

    public function update(Request $request, PbcComment $comment)
    {
        try {
            $this->authorize('edit_comment', $comment);

            $request->validate([
                'comment' => 'required|string|max:2000'
            ]);

            $updatedComment = $this->pbcCommentService->updateComment($comment, $request->comment);
            return $this->success($updatedComment, 'Comment updated successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to update comment', $e->getMessage(), 500);
        }
    }

    public function destroy(PbcComment $comment)
    {
        try {
            $this->authorize('delete_comment', $comment);

            $this->pbcCommentService->deleteComment($comment);
            return $this->success(null, 'Comment deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete comment', $e->getMessage(), 500);
        }
    }
}
