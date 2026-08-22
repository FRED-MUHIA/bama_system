<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'product_id',
        'type',
        'quantity',
        'balance_after',
        'source_type',
        'source_id',
        'reference',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'balance_after' => 'decimal:3',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
