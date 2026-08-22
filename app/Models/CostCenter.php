<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class CostCenter extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'department_id', 'parent_id', 'project_id', 'name', 'code', 'description', 'is_project', 'is_active', 'created_by', 'updated_by'];
    protected $casts = ['is_project' => 'boolean', 'is_active' => 'boolean'];

    public function department() { return $this->belongsTo(Department::class); }
    public function parent() { return $this->belongsTo(self::class, 'parent_id'); }
    public function children() { return $this->hasMany(self::class, 'parent_id'); }
    public function project() { return $this->belongsTo(Project::class); }
    public function budgets() { return $this->hasMany(AccountingBudget::class); }
}
