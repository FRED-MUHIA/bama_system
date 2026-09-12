<?php

namespace Modules\Chama\Models;

class Vote extends ChamaModel
{
    protected $table = 'chama_votes';

    protected $casts = [
        'metadata' => 'array',
        'voted_at' => 'datetime',
    ];

    public function ballot() { return $this->belongsTo(Ballot::class); }
    public function member() { return $this->belongsTo(Member::class); }
}
