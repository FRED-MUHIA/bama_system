<?php

namespace Modules\Chama\Models;

class Ballot extends ChamaModel
{
    protected $table = 'chama_ballots';

    protected $casts = [
        'is_secret' => 'boolean',
        'options' => 'array',
        'opens_at' => 'datetime',
        'closes_at' => 'datetime',
    ];

    public function votes() { return $this->hasMany(Vote::class); }
}
