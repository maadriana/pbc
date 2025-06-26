<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class PbcDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
    'pbc_request_id',
    'original_name',
    'file_name',
    'file_path',
    'file_type',
    'file_size',
    'mime_type',
    'uploaded_by',
    'status',
    'comments',
    'reviewed_by',
    'reviewed_at',
    'version',
    'is_latest_version',
    // NEW CLOUD FIELDS
    'cloud_url',
    'cloud_public_id',
    'cloud_provider',
    'metadata',
    'last_accessed_at',
];

protected $casts = [
    'file_size' => 'integer',
    'reviewed_at' => 'datetime',
    'last_accessed_at' => 'datetime',
    'is_latest_version' => 'boolean',
    'metadata' => 'array',
];

    // Relationships
    public function pbcRequest()
    {
        return $this->belongsTo(PbcRequest::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeLatestVersion($query)
    {
        return $query->where('is_latest_version', true);
    }

    // Helper methods
    public function approve($userId, $comments = null)
    {
        $this->update([
            'status' => 'approved',
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
            'comments' => $comments,
        ]);
    }

    public function reject($userId, $reason)
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
            'comments' => $reason,
        ]);
    }

    public function getFileUrl()
    {
        return Storage::url($this->file_path);
    }

    public function getFileSizeFormatted()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getStatusColorAttribute()
    {
        switch ($this->status) {
            case 'approved':
                return 'green';
            case 'rejected':
                return 'red';
            case 'pending':
                return 'yellow';
            default:
                return 'gray';
        }
    }

    public function getDisplayStatusAttribute()
    {
        return ucfirst($this->status);
    }

    public function deleteFile()
    {
        if (Storage::exists($this->file_path)) {
            Storage::delete($this->file_path);
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($document) {
            $document->deleteFile();
        });
    }
}
