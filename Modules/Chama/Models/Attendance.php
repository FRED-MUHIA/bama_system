<?php

namespace Modules\Chama\Models;

class Attendance extends ChamaModel
{
    protected $table = 'chama_attendance';

    protected $casts = [
        'checked_in_at' => 'datetime',
    ];

    public function meeting() { return $this->belongsTo(Meeting::class); }
    public function member() { return $this->belongsTo(Member::class); }
}
