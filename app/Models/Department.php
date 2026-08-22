<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'name', 'code', 'manager_id', 'description', 'is_active', 'created_by', 'updated_by'];
    protected $casts = ['is_active' => 'boolean'];

    public function manager() { return $this->belongsTo(User::class, 'manager_id'); }
    public function costCenters() { return $this->hasMany(CostCenter::class); }
    public function budgets() { return $this->hasMany(AccountingBudget::class); }
}
