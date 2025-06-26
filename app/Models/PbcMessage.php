<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PbcMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'message',
        'attachments',
        'is_read',
        'read_at',
        'message_type',
        'reply_to_id',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    // Relationships
    public function conversation()
    {
        return $this->belongsTo(PbcConversation::class, 'conversation_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function replyTo()
    {
        return $this->belongsTo(PbcMessage::class, 'reply_to_id');
    }

    public function replies()
    {
        return $this->hasMany(PbcMessage::class, 'reply_to_id');
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeForConversation($query, $conversationId)
    {
        return $query->where('conversation_id', $conversationId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('message_type', $type);
    }

    public function scopeWithAttachments($query)
    {
        return $query->whereNotNull('attachments');
    }

    // Helper methods
    public function hasAttachments()
    {
        return !empty($this->attachments);
    }

    public function getAttachmentCount()
    {
        return $this->attachments ? count($this->attachments) : 0;
    }

    public function markAsRead()
    {
        if (!$this->is_read) {
            $this->update(['is_read' => true, 'read_at' => now()]);
        }
    }

    public function isSystemMessage()
    {
        return $this->message_type === 'system';
    }

    public function isFileMessage()
    {
        return $this->message_type === 'file' || $this->hasAttachments();
    }
}
