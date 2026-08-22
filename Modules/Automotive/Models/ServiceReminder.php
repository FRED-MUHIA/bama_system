<?php

namespace Modules\Automotive\Models;

class ServiceReminder extends AutomotiveModel
{
    protected $table = 'automotive_service_reminders';

    protected $casts = ['due_date' => 'date'];

    public function vehicle() { return $this->belongsTo(Vehicle::class); }
}
