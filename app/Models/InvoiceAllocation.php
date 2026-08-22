<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class InvoiceAllocation extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'invoice_id', 'source_invoice_id', 'allocated_amount'];

    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function sourceInvoice() { return $this->belongsTo(Invoice::class, 'source_invoice_id'); }
    public function receiptAllocations() { return $this->hasMany(ReceiptAllocation::class); }
}
