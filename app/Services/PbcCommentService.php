<?php

namespace App\Services;

use App\Models\PbcComment;
use App\Models\PbcRequest;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Storage;

class PbcCommentService
{
    public function getPbcRequestComments(PbcRequest $pbcRequest, User $user): array
    {
        $query = $pbcRequest->comments()
            ->with(['user', 'replies.user'])
            ->whereNull('parent_id');

        // Filter internal comments for guests
        if ($user->isGuest()) {
            $query->where('is_internal', false);
        }

        $comments = $query->orderBy('created_at', 'asc')->get();

        return $comments->map(function ($comment) use ($user) {
            return [
                'id' => $comment->id,
                'comment' => $comment->comment,
                'user' => $comment->user->name,
                'user_role' => $comment->user->role,
                'is_internal' => $comment->is_internal,
                'created_at' => $comment->created_at,
                'formatted_date' => $comment->created_at->diffForHumans(),
                'attachments' => $comment->getAttachmentUrls(),
                'can_edit' => $this->canEditComment($comment, $user),
                'can_delete' => $this->canDeleteComment($comment, $user),
                'replies' => $comment->replies->map(function ($reply) use ($user) {
                    return [
                        'id' => $reply->id,
                        'comment' => $reply->comment,
                        'user' => $reply->user->name,
                        'user_role' => $reply->user->role,
                        'created_at' => $reply->created_at,
                        'formatted_date' => $reply->created_at->diffForHumans(),
                        'can_edit' => $this->canEditComment($reply, $user),
                        'can_delete' => $this->canDeleteComment($reply, $user),
                    ];
                })->toArray(),
            ];
        })->toArray();
    }

    public function createComment(array $data, User $user): PbcComment
    {
        $data['user_id'] = $user->id;

        // Handle attachments
        if (isset($data['attachments']) && !empty($data['attachments'])) {
            $attachmentPaths = [];
            foreach ($data['attachments'] as $attachment) {
                $path = $attachment->store('pbc-comments/' . date('Y/m'), 'public');
                $attachmentPaths[] = $path;
            }
            $data['attachments'] = $attachmentPaths;
        }

        $comment = PbcComment::create($data);

        $this->logActivity('comment_created', $comment, $user, 'Comment created');

        return $comment->load(['user', 'pbcRequest']);
    }

    public function updateComment(PbcComment $comment, string $newComment): PbcComment
    {
        $oldComment = $comment->comment;
        $comment->update(['comment' => $newComment]);

        $this->logActivity('comment_updated', $comment, auth()->user(), 'Comment updated');

        return $comment;
    }

    public function deleteComment(PbcComment $comment): bool
    {
        // Delete attachment files
        if ($comment->hasAttachments()) {
            foreach ($comment->attachments as $path) {
                Storage::disk('public')->delete($path);
            }
        }

        $this->logActivity('comment_deleted', $comment, auth()->user(), 'Comment deleted');

        return $comment->delete();
    }

    private function canEditComment(PbcComment $comment, User $user): bool
    {
        return $comment->user_id === $user->id || $user->isSystemAdmin();
    }

    private function canDeleteComment(PbcComment $comment, User $user): bool
    {
        return $comment->user_id === $user->id || $user->isSystemAdmin();
    }

    private function logActivity(string $action, PbcComment $comment, User $user, string $description): void
    {
        AuditLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'model_type' => PbcComment::class,
            'model_id' => $comment->id,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
