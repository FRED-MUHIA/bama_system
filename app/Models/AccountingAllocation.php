<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class AccountingAllocation extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'transaction_type', 'transaction_id', 'department_id', 'cost_center_id', 'project_id', 'direction', 'category', 'description', 'transaction_date', 'amount', 'created_by'];
    protected $casts = ['transaction_date' => 'date', 'amount' => 'decimal:2'];

    public function department() { return $this->belongsTo(Department::class); }
    public function costCenter() { return $this->belongsTo(CostCenter::class); }
    public function project() { return $this->belongsTo(Project::class); }
}
