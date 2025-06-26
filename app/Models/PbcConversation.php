<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PbcConversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'project_id',
        'title',
        'status',
        'last_message_at',
        'created_by',
        'metadata',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function messages()
    {
        return $this->hasMany(PbcMessage::class, 'conversation_id');
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'pbc_conversation_participants', 'conversation_id', 'user_id')
                    ->withPivot(['joined_at', 'last_read_at', 'is_active', 'role', 'settings'])
                    ->withTimestamps();
    }

    public function activeParticipants()
    {
        return $this->participants()->wherePivot('is_active', true);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lastMessage()
    {
        return $this->hasOne(PbcMessage::class, 'conversation_id')->latest();
    }

    public function unreadMessages()
    {
        return $this->hasMany(PbcMessage::class, 'conversation_id')->where('is_read', false);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForUser($query, $userId)
  {
    return $query->whereHas('participants', function ($q) use ($userId) {
        $q->where('pbc_conversation_participants.user_id', $userId)
          ->where('pbc_conversation_participants.is_active', true);
    });
  }

    public function scopeWithUnreadCount($query, $userId)
    {
        return $query->withCount(['unreadMessages as unread_count' => function ($q) use ($userId) {
            $q->where('sender_id', '!=', $userId);
        }]);
    }

    // Helper methods
    public function getUnreadCountForUser($userId)
    {
        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->count();
    }

    public function addParticipant($userId, $role = 'participant')
    {
        return $this->participants()->attach($userId, [
            'joined_at' => now(),
            'role' => $role,
            'is_active' => true
        ]);
    }

    public function removeParticipant($userId)
    {
        return $this->participants()->updateExistingPivot($userId, [
            'is_active' => false
        ]);
    }

    public function markAsReadForUser($userId)
    {
        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
    }
}
