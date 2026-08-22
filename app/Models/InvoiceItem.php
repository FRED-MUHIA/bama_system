<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = ['invoice_id', 'title', 'description', 'quantity', 'unit_price', 'discount', 'tax_rate', 'line_total'];
    public function invoice() { return $this->belongsTo(Invoice::class); }
}
