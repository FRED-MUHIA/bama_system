<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationItem extends Model
{
    protected $fillable = ['quotation_id', 'title', 'description', 'quantity', 'unit_price', 'discount', 'tax_rate', 'line_total'];
    public function quotation() { return $this->belongsTo(Quotation::class); }
}
