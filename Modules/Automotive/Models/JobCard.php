<?php

namespace Modules\Automotive\Models;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\User;

class JobCard extends AutomotiveModel
{
    protected $table = 'automotive_job_cards';

    protected $casts = ['estimated_completion' => 'datetime', 'meta' => 'array'];

    public function client() { return $this->belongsTo(Client::class); }
    public function vehicle() { return $this->belongsTo(Vehicle::class); }
    public function booking() { return $this->belongsTo(ServiceBooking::class, 'booking_id'); }
    public function checkIn() { return $this->belongsTo(CheckIn::class, 'check_in_id'); }
    public function serviceAdvisor() { return $this->belongsTo(User::class, 'service_advisor_id'); }
    public function technician() { return $this->belongsTo(User::class, 'technician_id'); }
    public function workshopBay() { return $this->belongsTo(WorkshopBay::class); }
    public function quotation() { return $this->belongsTo(Quotation::class); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function inspections() { return $this->hasMany(Inspection::class); }
    public function labourTasks() { return $this->hasMany(LabourTask::class); }
    public function partRequests() { return $this->hasMany(PartRequest::class); }
    public function estimates() { return $this->hasMany(Estimate::class); }
    public function qualityChecks() { return $this->hasMany(QualityCheck::class); }
    public function roadTests() { return $this->hasMany(RoadTest::class); }
    public function costs() { return $this->hasOne(JobCost::class); }
}
