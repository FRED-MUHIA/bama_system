<?php

namespace Modules\Chama\Models;

class WelfareRequest extends ChamaModel
{
    protected $table = 'chama_welfare_requests';

    protected $casts = [
        'supporting_documents' => 'array',
        'approved_at' => 'datetime',
        'payment_date' => 'date',
    ];

    public function member() { return $this->belongsTo(Member::class); }
}
