<?php

namespace Modules\Chama\Models;

class ShareTransaction extends ChamaModel
{
    protected $table = 'chama_share_transactions';

    protected $casts = [
        'transaction_date' => 'date',
    ];

    public function member() { return $this->belongsTo(Member::class); }
}
