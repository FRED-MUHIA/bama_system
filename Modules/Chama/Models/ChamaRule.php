<?php

namespace Modules\Chama\Models;

class ChamaRule extends ChamaModel
{
    protected $table = 'chama_rules';

    protected $casts = [
        'rules' => 'array',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'approved_at' => 'datetime',
    ];
}
