<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PbcTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'engagement_types',
        'is_default',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'engagement_types' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function templateItems()
    {
        return $this->hasMany(PbcTemplateItem::class, 'template_id');
    }

    public function pbcRequests()
    {
        return $this->hasMany(PbcRequest::class, 'template_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeForEngagementType($query, $engagementType)
    {
        return $query->whereJsonContains('engagement_types', $engagementType)
                    ->orWhereNull('engagement_types');
    }

    // Helper methods
    public function getItemsGroupedByCategory()
    {
        return $this->templateItems()
            ->with('category')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category.name');
    }

    public function getTotalItemsCount()
    {
        return $this->templateItems()->where('is_active', true)->count();
    }

    public function canBeUsedForEngagement($engagementType)
    {
        if (empty($this->engagement_types)) {
            return true; // Can be used for all engagement types
        }

        return in_array($engagementType, $this->engagement_types);
    }

    public function duplicateTemplate($newName, $userId)
    {
        $newTemplate = $this->replicate();
        $newTemplate->name = $newName;
        $newTemplate->code = \Str::slug($newName);
        $newTemplate->is_default = false;
        $newTemplate->created_by = $userId;
        $newTemplate->save();

        // Duplicate all template items
        foreach ($this->templateItems as $item) {
            $newItem = $item->replicate();
            $newItem->template_id = $newTemplate->id;
            $newItem->save();
        }

        return $newTemplate;
    }
}
