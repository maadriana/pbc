<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class PbcComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pbc_request_id',
        'user_id',
        'comment',
        'is_internal',
        'parent_id',
        'attachments',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
        'attachments' => 'array',
    ];

    // Relationships
    public function pbcRequest()
    {
        return $this->belongsTo(PbcRequest::class);
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
        return $this->hasMany(PbcComment::class, 'parent_id');
    }

    // Scopes
    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    public function scopeExternal($query)
    {
        return $query->where('is_internal', false);
    }

    public function scopeParentComments($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeReplies($query)
    {
        return $query->whereNotNull('parent_id');
    }

    // Helper methods
    public function hasAttachments()
    {
        return !empty($this->attachments);
    }

    public function getAttachmentUrls()
    {
        if (!$this->hasAttachments()) {
            return [];
        }

        return collect($this->attachments)->map(function ($path) {
            return Storage::url($path);
        })->toArray();
    }
}
