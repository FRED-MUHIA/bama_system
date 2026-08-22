<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use App\Models\Concerns\AuditsAccountingChanges;
use Illuminate\Database\Eloquent\Model;

class ProjectExpense extends Model
{
    use BelongsToBusiness, AuditsAccountingChanges;

    protected $fillable = ['business_id', 'project_id', 'department_id', 'cost_center_id', 'expense_date', 'category', 'description', 'amount'];
    protected $casts = ['expense_date' => 'date'];

    public function project() { return $this->belongsTo(Project::class); }
}
