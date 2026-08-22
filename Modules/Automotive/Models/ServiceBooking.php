<?php

namespace Modules\Automotive\Models;

use App\Models\Client;
use App\Models\User;

class ServiceBooking extends AutomotiveModel
{
    protected $table = 'automotive_service_bookings';

    protected $casts = ['preferred_date' => 'date', 'pickup_required' => 'boolean', 'dropoff_required' => 'boolean'];

    public function client() { return $this->belongsTo(Client::class); }
    public function vehicle() { return $this->belongsTo(Vehicle::class); }
    public function serviceAdvisor() { return $this->belongsTo(User::class, 'service_advisor_id'); }
    public function jobCard() { return $this->hasOne(JobCard::class, 'booking_id'); }
}
