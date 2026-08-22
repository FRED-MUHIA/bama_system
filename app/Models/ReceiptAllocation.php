<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class ReceiptAllocation extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'receipt_id', 'invoice_id', 'invoice_allocation_id', 'project_id', 'amount'];

    public function receipt() { return $this->belongsTo(Receipt::class); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function invoiceAllocation() { return $this->belongsTo(InvoiceAllocation::class); }
    public function project() { return $this->belongsTo(Project::class); }
}
