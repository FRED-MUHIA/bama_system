<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class SupplierQuote extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'supplier_id', 'project_id', 'department_id', 'cost_center_id', 'quote_number', 'amount', 'status', 'notes'];

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function project() { return $this->belongsTo(Project::class); }
}
