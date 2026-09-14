<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductAttribute extends Model
{
    use BelongsToBusiness;
    use SoftDeletes;

    public const DISPLAY_TYPES = [
        'Dropdown',
        'Radio',
        'Button',
        'Color Swatch',
        'Numeric',
        'Text',
        'Boolean',
    ];

    protected $guarded = [];

    protected $casts = [
        'is_variant_attribute' => 'boolean',
        'is_filterable' => 'boolean',
        'is_searchable' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function values()
    {
        return $this->hasMany(ProductAttributeValue::class)->orderBy('sort_order')->orderBy('value');
    }

    public function categoryTemplates()
    {
        return $this->hasMany(CategoryAttribute::class);
    }

    public function productAssignments()
    {
        return $this->hasMany(ProductAttributeAssignment::class);
    }
}
