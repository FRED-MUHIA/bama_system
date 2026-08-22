<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class AccountingBudget extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'department_id', 'cost_center_id', 'project_id', 'name', 'fiscal_year', 'amount', 'alert_threshold', 'status', 'created_by', 'approved_by', 'approved_at'];
    protected $casts = ['amount' => 'decimal:2', 'alert_threshold' => 'decimal:2', 'approved_at' => 'datetime'];

    public function department() { return $this->belongsTo(Department::class); }
    public function costCenter() { return $this->belongsTo(CostCenter::class); }
    public function project() { return $this->belongsTo(Project::class); }
}
