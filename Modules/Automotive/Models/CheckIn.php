<?php

namespace Modules\Automotive\Models;

use App\Models\Client;
use App\Models\User;

class CheckIn extends AutomotiveModel
{
    protected $table = 'automotive_check_ins';

    protected $casts = ['checked_in_at' => 'datetime', 'expected_completion' => 'datetime', 'existing_damage' => 'array', 'accessories' => 'array', 'warning_lights' => 'array', 'photos' => 'array', 'keys_received' => 'boolean'];

    public function booking() { return $this->belongsTo(ServiceBooking::class, 'booking_id'); }
    public function client() { return $this->belongsTo(Client::class); }
    public function vehicle() { return $this->belongsTo(Vehicle::class); }
    public function serviceAdvisor() { return $this->belongsTo(User::class, 'service_advisor_id'); }
}
