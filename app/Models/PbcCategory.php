<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PbcCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function templateItems()
    {
        return $this->hasMany(PbcTemplateItem::class, 'category_id');
    }

    public function requestItems()
    {
        return $this->hasMany(PbcRequestItem::class, 'category_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // Helper methods
    public function getActiveTemplateItemsCount()
    {
        return $this->templateItems()->where('is_active', true)->count();
    }

    public function getDisplayNameAttribute()
    {
        return $this->name;
    }
}
