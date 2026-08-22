<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use App\Models\Concerns\AuditsAccountingChanges;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use BelongsToBusiness, AuditsAccountingChanges;

    protected $fillable = ['business_id', 'supplier_id', 'project_id', 'department_id', 'cost_center_id', 'po_number', 'order_date', 'amount', 'status', 'notes'];
    protected $casts = ['order_date' => 'date'];

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function project() { return $this->belongsTo(Project::class); }
    public function goodsReceivedNotes() { return $this->hasMany(GoodsReceivedNote::class); }
    public function supplierInvoices() { return $this->hasMany(SupplierInvoice::class); }
}
