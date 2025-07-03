<?php

namespace App\Services;

use App\Models\PbcComment;
use App\Models\PbcRequest;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;

class PbcCommentService
{
    public function getPbcRequestComments(PbcRequest $pbcRequest, User $user): array
    {
        $comments = $pbcRequest->comments()
            ->with(['user', 'replies.user', 'resolver'])
            ->whereNull('parent_id') // Only top-level comments
            ->where(function($query) use ($user) {
                if ($user->isGuest()) {
                    $query->whereIn('visibility', ['client', 'both']);
                } else {
                    $query->whereIn('visibility', ['internal', 'both']);
                }
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return $comments->map(function($comment) use ($user) {
            return [
                'id' => $comment->id,
                'comment' => $comment->comment,
                'type' => $comment->type,
                'visibility' => $comment->visibility,
                'user' => [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'role' => $comment->user->role,
                ],
                'is_resolved' => $comment->is_resolved,
                'resolved_by' => $comment->resolver?->name,
                'resolved_at' => $comment->resolved_at?->diffForHumans(),
                'created_at' => $comment->created_at->diffForHumans(),
                'can_edit' => $comment->canBeEditedBy($user),
                'can_delete' => $comment->canBeDeletedBy($user),
                'can_resolve' => $comment->canBeResolvedBy($user),
                'replies' => $comment->replies->map(function($reply) use ($user) {
                    return [
                        'id' => $reply->id,
                        'comment' => $reply->comment,
                        'user' => [
                            'id' => $reply->user->id,
                            'name' => $reply->user->name,
                            'role' => $reply->user->role,
                        ],
                        'created_at' => $reply->created_at->diffForHumans(),
                        'can_edit' => $reply->canBeEditedBy($user),
                        'can_delete' => $reply->canBeDeletedBy($user),
                    ];
                })->toArray(),
            ];
        })->toArray();
    }

    public function createComment(array $commentData, User $user): PbcComment
    {
        DB::beginTransaction();

        try {
            $comment = PbcComment::create(array_merge($commentData, [
                'user_id' => $user->id,
            ]));

            // Log activity
            AuditLog::logPbcActivity('commented', $comment,
                "Comment added: " . substr($comment->comment, 0, 50) . "...", $user);

            DB::commit();
            return $comment->load(['user', 'commentable']);

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function updateComment(PbcComment $comment, string $newContent): PbcComment
    {
        DB::beginTransaction();

        try {
            $oldContent = $comment->comment;
            $comment->update(['comment' => $newContent]);

            // Log activity
            AuditLog::logPbcActivity('updated_comment', $comment,
                "Comment updated", auth()->user());

            DB::commit();
            return $comment->fresh(['user']);

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function deleteComment(PbcComment $comment): bool
    {
        DB::beginTransaction();

        try {
            // Log activity before deletion
            AuditLog::logPbcActivity('deleted_comment', $comment,
                "Comment deleted", auth()->user());

            $result = $comment->delete();

            DB::commit();
            return $result;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function replyToComment(PbcComment $parentComment, string $content, User $user, string $visibility = null): PbcComment
    {
        DB::beginTransaction();

        try {
            $reply = $parentComment->reply($content, $user, 'general', $visibility);

            // Log activity
            AuditLog::logPbcActivity('replied', $reply,
                "Reply added to comment", $user);

            DB::commit();
            return $reply->load(['user']);

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function resolveComment(PbcComment $comment, User $user): PbcComment
    {
        DB::beginTransaction();

        try {
            $comment->markAsResolved($user);

            DB::commit();
            return $comment->fresh(['resolver']);

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
