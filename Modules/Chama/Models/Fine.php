<?php

namespace Modules\Chama\Models;

class Fine extends ChamaModel
{
    protected $table = 'chama_fines';

    protected $casts = [
        'fine_date' => 'date',
        'waived_at' => 'datetime',
    ];

    public function member() { return $this->belongsTo(Member::class); }
}
