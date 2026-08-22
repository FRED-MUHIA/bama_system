<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class BusinessTemplate extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'template_category_id', 'name', 'type', 'default_subject',
        'content', 'output_format', 'is_system', 'is_active', 'source_template_id',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(TemplateCategory::class, 'template_category_id');
    }
}
