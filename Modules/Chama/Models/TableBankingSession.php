<?php

namespace Modules\Chama\Models;

class TableBankingSession extends ChamaModel
{
    protected $table = 'chama_table_banking_sessions';

    protected $casts = [
        'session_date' => 'date',
        'is_balanced' => 'boolean',
        'notes' => 'array',
    ];
}
