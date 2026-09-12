<?php

namespace Modules\Chama\Models;

class CashHandover extends ChamaModel
{
    protected $table = 'chama_cash_handovers';

    protected $casts = [
        'handed_over_at' => 'datetime',
        'received_at' => 'datetime',
    ];
}
