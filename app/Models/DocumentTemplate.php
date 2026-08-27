<?php

namespace App\Models;

use App\Casts\TolerantEncryptedString;
use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class DocumentTemplate extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'name', 'type', 'content', 'output_format', 'is_active'];
    protected $casts = [
        'is_active' => 'boolean',
        'content' => TolerantEncryptedString::class,
    ];

    public function documents() { return $this->hasMany(ProjectDocument::class); }
}
