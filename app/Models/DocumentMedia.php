<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentMedia extends Model
{
    protected $fillable = [
        'business_id', 'model_type', 'model_id', 'file_path', 'file_name',
        'mime_type', 'file_size', 'disk', 'caption',
    ];

    public function model()
    {
        return $this->morphTo();
    }
}
