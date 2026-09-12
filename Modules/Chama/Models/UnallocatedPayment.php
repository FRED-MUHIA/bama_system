<?php

namespace Modules\Chama\Models;

class UnallocatedPayment extends ChamaModel
{
    protected $table = 'chama_unallocated_payments';

    protected $casts = [
        'payload' => 'array',
        'allocated_at' => 'datetime',
    ];
}
