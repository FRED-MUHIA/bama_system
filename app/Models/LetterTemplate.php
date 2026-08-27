<?php

namespace App\Models;

use App\Casts\TolerantEncryptedString;
use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class LetterTemplate extends Model
{
    use BelongsToBusiness;

    public const TYPES = ['Commercial', 'Financial', 'Project', 'Technical', 'Legal', 'General', 'Warranty', 'Procurement'];

    protected $fillable = [
        'business_id', 'template_category_id', 'name', 'type', 'default_subject', 'content',
        'content_type', 'output_format', 'is_active', 'is_system', 'source_template_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'content' => TolerantEncryptedString::class,
    ];

    public function letters() { return $this->hasMany(Letter::class); }
    public function category() { return $this->belongsTo(TemplateCategory::class, 'template_category_id'); }
}
