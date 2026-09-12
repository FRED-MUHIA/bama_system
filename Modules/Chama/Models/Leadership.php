<?php

namespace Modules\Chama\Models;

class Leadership extends ChamaModel
{
    protected $table = 'chama_leadership';

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function member() { return $this->belongsTo(Member::class); }
}
