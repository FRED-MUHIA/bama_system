<?php

namespace Modules\Automotive\Models;

use App\Models\Client;
use App\Models\User;

class Complaint extends AutomotiveModel
{
    protected $table = 'automotive_complaints';

    public function client() { return $this->belongsTo(Client::class); }
    public function vehicle() { return $this->belongsTo(Vehicle::class); }
    public function jobCard() { return $this->belongsTo(JobCard::class); }
    public function assignedEmployee() { return $this->belongsTo(User::class, 'assigned_employee_id'); }
}
