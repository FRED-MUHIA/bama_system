<?php

namespace Modules\Chama\Models;

class Meeting extends ChamaModel
{
    protected $table = 'chama_meetings';

    protected $casts = [
        'meeting_date' => 'date',
        'quorum_achieved' => 'boolean',
    ];

    public function attendance() { return $this->hasMany(Attendance::class); }
    public function agendaItems() { return $this->hasMany(AgendaItem::class); }
    public function minutes() { return $this->hasMany(Minute::class); }
}
