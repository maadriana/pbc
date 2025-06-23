<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PbcReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'pbc_request_id',
        'sent_by',
        'sent_to',
        'subject',
        'message',
        'type',
        'sent_at',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    // Relationships
    public function pbcRequest()
    {
        return $this->belongsTo(PbcRequest::class);
    }

    public function sentBy()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function sentTo()
    {
        return $this->belongsTo(User::class, 'sent_to');
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Helper methods
    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function getDisplayTypeAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->type));
    }
}
