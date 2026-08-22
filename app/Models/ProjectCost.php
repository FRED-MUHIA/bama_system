<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class ProjectCost extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'project_id', 'category', 'description', 'expected_amount', 'actual_amount'];

    public function project() { return $this->belongsTo(Project::class); }
}
