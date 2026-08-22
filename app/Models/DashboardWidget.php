<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardWidget extends Model
{
    protected $fillable = ['slug', 'name', 'module_slug', 'industry', 'component', 'permission', 'settings_schema', 'is_active'];

    protected $casts = ['settings_schema' => 'array', 'is_active' => 'boolean'];
}
