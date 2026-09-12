<?php

namespace Modules\Chama\Models;

class PaymentAllocation extends ChamaModel
{
    protected $table = 'chama_payment_allocations';

    protected $casts = [
        'payment_date' => 'date',
        'metadata' => 'array',
    ];

    public function member() { return $this->belongsTo(Member::class); }
    public function lines() { return $this->hasMany(PaymentAllocationLine::class); }
}
