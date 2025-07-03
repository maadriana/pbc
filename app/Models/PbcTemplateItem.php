<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PbcTemplateItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'category_id',
        'parent_id',
        'item_number',
        'sub_item_letter',
        'description',
        'sort_order',
        'is_required',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    // Relationships
    public function template()
    {
        return $this->belongsTo(PbcTemplate::class, 'template_id');
    }

    public function category()
    {
        return $this->belongsTo(PbcCategory::class, 'category_id');
    }

    public function parent()
    {
        return $this->belongsTo(PbcTemplateItem::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(PbcTemplateItem::class, 'parent_id')->orderBy('sort_order');
    }

    public function requestItems()
    {
        return $this->hasMany(PbcRequestItem::class, 'template_item_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMainItems($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeSubItems($query)
    {
        return $query->whereNotNull('parent_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    // Helper methods
    public function getFullItemNumber()
    {
        if ($this->parent_id) {
            return $this->parent->item_number . $this->sub_item_letter;
        }
        return $this->item_number;
    }

    public function getDisplayName()
    {
        $prefix = $this->getFullItemNumber();
        return $prefix ? "{$prefix}. {$this->description}" : $this->description;
    }

    public function isMainItem()
    {
        return is_null($this->parent_id);
    }

    public function isSubItem()
    {
        return !is_null($this->parent_id);
    }

    public function hasChildren()
    {
        return $this->children()->count() > 0;
    }

    public function getDepthLevel()
    {
        $level = 0;
        $current = $this;

        while ($current->parent_id) {
            $level++;
            $current = $current->parent;
        }

        return $level;
    }

    public function getAllDescendants()
    {
        $descendants = collect();

        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getAllDescendants());
        }

        return $descendants;
    }
}
