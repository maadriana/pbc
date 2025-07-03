<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PbcComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'commentable_type',
        'commentable_id',
        'comment',
        'type',
        'visibility',
        'user_id',
        'parent_id',
        'is_resolved',
        'resolved_at',
        'resolved_by',
        'attachments',
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'attachments' => 'array',
    ];

    // Relationships
    public function commentable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(PbcComment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(PbcComment::class, 'parent_id')->orderBy('created_at');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'model');
    }

    // Scopes
    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeResolved($query)
    {
        return $query->where('is_resolved', true);
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeReplies($query)
    {
        return $query->whereNotNull('parent_id');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByVisibility($query, $visibility)
    {
        return $query->where('visibility', $visibility);
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isGuest()) {
            return $query->whereIn('visibility', ['client', 'both']);
        } else {
            return $query->whereIn('visibility', ['internal', 'both']);
        }
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Helper methods
    public function isTopLevel()
    {
        return is_null($this->parent_id);
    }

    public function isReply()
    {
        return !is_null($this->parent_id);
    }

    public function hasReplies()
    {
        return $this->replies()->count() > 0;
    }

    public function getRepliesCount()
    {
        return $this->replies()->count();
    }

    public function getUnresolvedRepliesCount()
    {
        return $this->replies()->unresolved()->count();
    }

    public function isVisibleTo(User $user)
    {
        if ($user->isGuest()) {
            return in_array($this->visibility, ['client', 'both']);
        } else {
            return in_array($this->visibility, ['internal', 'both']);
        }
    }

    public function canBeEditedBy(User $user)
    {
        // System admin can edit all
        if ($user->isSystemAdmin()) {
            return true;
        }

        // Author can edit within 30 minutes
        if ($this->user_id === $user->id) {
            return $this->created_at->diffInMinutes(now()) <= 30;
        }

        return false;
    }

    public function canBeDeletedBy(User $user)
    {
        // System admin can delete all
        if ($user->isSystemAdmin()) {
            return true;
        }

        // Author can delete within 5 minutes
        if ($this->user_id === $user->id) {
            return $this->created_at->diffInMinutes(now()) <= 5;
        }

        // Project managers can delete
        if ($this->commentable_type === PbcRequest::class) {
            $request = $this->commentable;
            $project = $request->project;
            return in_array($user->id, [$project->engagement_partner_id, $project->manager_id]);
        }

        return false;
    }

    public function canBeResolvedBy(User $user)
    {
        // Guests cannot resolve comments
        if ($user->isGuest()) {
            return false;
        }

        // System admin can resolve all
        if ($user->isSystemAdmin()) {
            return true;
        }

        // Author can resolve their own comments
        if ($this->user_id === $user->id) {
            return true;
        }

        // Project team members can resolve
        if ($this->commentable_type === PbcRequest::class) {
            $request = $this->commentable;
            $teamMemberIds = $request->project->getTeamMembers()->pluck('id')->toArray();
            return in_array($user->id, $teamMemberIds);
        }

        return false;
    }

    public function getTypeBadgeClass()
    {
        return match($this->type) {
            'general' => 'badge-secondary',
            'question' => 'badge-info',
            'clarification' => 'badge-warning',
            'issue' => 'badge-danger',
            'reminder' => 'badge-primary',
            default => 'badge-light'
        };
    }

    public function getDisplayTypeAttribute()
    {
        return ucfirst($this->type);
    }

    public function getDisplayVisibilityAttribute()
    {
        return match($this->visibility) {
            'internal' => 'Internal Only',
            'client' => 'Client Only',
            'both' => 'Everyone',
            default => $this->visibility
        };
    }

    public function markAsResolved(User $user)
    {
        $this->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => $user->id,
        ]);

        // Log the activity
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'resolved',
            'model_type' => self::class,
            'model_id' => $this->id,
            'description' => "Comment marked as resolved",
            'category' => 'pbc_request',
            'severity' => 'low',
        ]);

        return $this;
    }

    public function markAsUnresolved()
    {
        $this->update([
            'is_resolved' => false,
            'resolved_at' => null,
            'resolved_by' => null,
        ]);

        return $this;
    }

    public function reply($content, User $user, $type = 'general', $visibility = null)
    {
        // If no visibility specified, inherit from parent
        if ($visibility === null) {
            $visibility = $this->visibility;
        }

        $reply = static::create([
            'commentable_type' => $this->commentable_type,
            'commentable_id' => $this->commentable_id,
            'comment' => $content,
            'type' => $type,
            'visibility' => $visibility,
            'user_id' => $user->id,
            'parent_id' => $this->id,
        ]);

        return $reply;
    }

    public function hasAttachments()
    {
        return !empty($this->attachments);
    }

    public function getAttachmentsCount()
    {
        return count($this->attachments ?? []);
    }

    public function addAttachment($filePath, $originalName, $mimeType, $size)
    {
        $attachments = $this->attachments ?? [];

        $attachments[] = [
            'file_path' => $filePath,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'size' => $size,
            'uploaded_at' => now()->toISOString(),
        ];

        $this->update(['attachments' => $attachments]);

        return $this;
    }

    public function removeAttachment($index)
    {
        $attachments = $this->attachments ?? [];

        if (isset($attachments[$index])) {
            // Delete the actual file
            $filePath = $attachments[$index]['file_path'];
            \Storage::disk('pbc-comments')->delete($filePath);

            // Remove from array
            unset($attachments[$index]);
            $attachments = array_values($attachments); // Re-index array

            $this->update(['attachments' => $attachments]);
        }

        return $this;
    }

    public function getThreadComments()
    {
        if ($this->isReply()) {
            return $this->parent->getThreadComments();
        }

        return static::where('parent_id', $this->id)
                    ->orWhere('id', $this->id)
                    ->orderBy('created_at')
                    ->get();
    }

    public function getCommentableDisplayName()
    {
        switch ($this->commentable_type) {
            case PbcRequest::class:
                return "PBC Request: {$this->commentable->title}";
            case PbcRequestItem::class:
                return "PBC Item: {$this->commentable->getDisplayName()}";
            case PbcSubmission::class:
                return "Document: {$this->commentable->original_filename}";
            default:
                return $this->commentable_type;
        }
    }

    public function getCreatedAtFormatted()
    {
        return $this->created_at->format('M j, Y \a\t g:i A');
    }

    public function getResolvedAtFormatted()
    {
        return $this->resolved_at ? $this->resolved_at->format('M j, Y \a\t g:i A') : null;
    }

    public function getTimeAgo()
    {
        return $this->created_at->diffForHumans();
    }

    public function mention(User $user)
    {
        // Create a notification for the mentioned user
        // This would integrate with your notification system

        // For now, we'll just log it
        AuditLog::create([
            'user_id' => $this->user_id,
            'action' => 'mentioned',
            'model_type' => User::class,
            'model_id' => $user->id,
            'description' => "User {$user->name} mentioned in comment",
            'category' => 'pbc_request',
            'severity' => 'low',
        ]);

        return $this;
    }
}
