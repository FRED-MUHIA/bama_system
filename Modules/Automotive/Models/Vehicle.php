<?php

namespace Modules\Automotive\Models;

use App\Models\Client;

class Vehicle extends AutomotiveModel
{
    protected $table = 'automotive_vehicles';

    protected $casts = ['meta' => 'array', 'inspection_expiry' => 'date', 'last_service_date' => 'date', 'next_service_date' => 'date'];

    public function client() { return $this->belongsTo(Client::class); }
    public function fleet() { return $this->belongsTo(Fleet::class); }
    public function bookings() { return $this->hasMany(ServiceBooking::class); }
    public function checkIns() { return $this->hasMany(CheckIn::class); }
    public function inspections() { return $this->hasMany(Inspection::class); }
    public function jobCards() { return $this->hasMany(JobCard::class); }
    public function releases() { return $this->hasMany(VehicleRelease::class); }
    public function warranties() { return $this->hasMany(Warranty::class); }
    public function reminders() { return $this->hasMany(ServiceReminder::class); }
}
