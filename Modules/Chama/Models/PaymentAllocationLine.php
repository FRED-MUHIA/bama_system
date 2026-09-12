<?php

namespace Modules\Chama\Models;

class PaymentAllocationLine extends ChamaModel
{
    protected $table = 'chama_payment_allocation_lines';

    protected $casts = [
        'metadata' => 'array',
    ];

    public function paymentAllocation() { return $this->belongsTo(PaymentAllocation::class); }
    public function allocatable() { return $this->morphTo(); }
}
