<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'tenant_id', 'business_id', 'parent_id', 'name', 'code', 'slug', 'description',
        'image_path', 'icon', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent() { return $this->belongsTo(self::class, 'parent_id'); }
    public function children() { return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name'); }
    public function products() { return $this->hasMany(Product::class); }
    public function attributeTemplates() { return $this->hasMany(CategoryAttribute::class)->orderBy('sort_order'); }
    public function attributes() { return $this->belongsToMany(ProductAttribute::class, 'category_attributes')->withPivot(['is_required', 'is_variant_attribute', 'sort_order', 'metadata'])->withTimestamps(); }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function path(string $separator = ' > '): string
    {
        $segments = [];
        $category = $this;

        while ($category) {
            array_unshift($segments, $category->name);
            $category = $category->relationLoaded('parent') ? $category->parent : $category->parent()->first();
        }

        return implode($separator, $segments);
    }
}
