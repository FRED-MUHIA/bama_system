<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndustryModule extends Model
{
    protected $fillable = ['industry', 'module_id', 'enabled_by_default'];

    protected $casts = ['enabled_by_default' => 'boolean'];

    public function module() { return $this->belongsTo(Module::class); }
}
