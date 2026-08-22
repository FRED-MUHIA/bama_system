<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class TemplateCategory extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'name', 'slug', 'description', 'icon', 'sort_order', 'is_system',
    ];

    protected $casts = ['is_system' => 'boolean'];

    public function businessTemplates()
    {
        return $this->hasMany(BusinessTemplate::class);
    }

    public function letterTemplates()
    {
        return $this->hasMany(LetterTemplate::class);
    }
}
