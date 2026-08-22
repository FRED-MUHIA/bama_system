<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = ['slug', 'name', 'namespace', 'type', 'industry', 'icon', 'route', 'permissions', 'menu', 'widgets', 'is_core', 'is_active'];

    protected $casts = ['permissions' => 'array', 'menu' => 'array', 'widgets' => 'array', 'is_core' => 'boolean', 'is_active' => 'boolean'];

    public function tenants() { return $this->belongsToMany(Tenant::class, 'tenant_modules')->using(TenantModule::class)->withPivot(['enabled', 'settings', 'enabled_at'])->withTimestamps(); }
}
