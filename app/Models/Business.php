<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'name', 'industry', 'slug', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function branches() { return $this->hasMany(Branch::class); }
    public function departments() { return $this->hasMany(Department::class); }
    public function teams() { return $this->hasMany(Team::class); }
}
