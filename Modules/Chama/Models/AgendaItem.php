<?php

namespace Modules\Chama\Models;

class AgendaItem extends ChamaModel
{
    protected $table = 'chama_agenda_items';

    protected $casts = [
        'due_date' => 'date',
    ];

    public function meeting() { return $this->belongsTo(Meeting::class); }
}
