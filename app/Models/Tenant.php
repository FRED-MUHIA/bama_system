<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'slug', 'industry', 'sub_industry', 'status', 'primary_domain', 'settings', 'trial_ends_at'];

    protected $casts = ['settings' => 'array', 'trial_ends_at' => 'datetime'];

    public function businesses() { return $this->hasMany(Business::class); }
    public function users() { return $this->belongsToMany(User::class)->withPivot(['role', 'status', 'joined_at'])->withTimestamps(); }
    public function modules() { return $this->belongsToMany(Module::class, 'tenant_modules')->using(TenantModule::class)->withPivot(['enabled', 'settings', 'enabled_at'])->withTimestamps(); }
    public function subscription() { return $this->hasOne(Subscription::class)->latestOfMany(); }
    public function theme() { return $this->hasOne(TenantTheme::class); }
    public function dashboardWidgets() { return $this->hasMany(TenantDashboardWidget::class); }
}
