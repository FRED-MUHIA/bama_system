<?php

namespace Modules\Salon\Models;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\PosOrder;

class Appointment extends SalonModel
{
    protected $table = 'salon_appointments';

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function client() { return $this->belongsTo(Client::class); }
    public function profile() { return $this->belongsTo(ClientProfile::class, 'salon_client_profile_id'); }
    public function staff() { return $this->belongsTo(StaffProfile::class, 'salon_staff_profile_id'); }
    public function resource() { return $this->belongsTo(Resource::class, 'salon_resource_id'); }
    public function posOrder() { return $this->belongsTo(PosOrder::class); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function services() { return $this->hasMany(AppointmentService::class, 'salon_appointment_id'); }
    public function productConsumptions() { return $this->hasMany(ProductConsumption::class, 'salon_appointment_id'); }
    public function commissions() { return $this->hasMany(Commission::class, 'salon_appointment_id'); }
}
