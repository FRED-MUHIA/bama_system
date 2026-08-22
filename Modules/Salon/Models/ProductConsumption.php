<?php

namespace Modules\Salon\Models;

use App\Models\Product;

class ProductConsumption extends SalonModel
{
    protected $table = 'salon_product_consumptions';

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function appointment() { return $this->belongsTo(Appointment::class, 'salon_appointment_id'); }
    public function service() { return $this->belongsTo(Service::class, 'salon_service_id'); }
    public function product() { return $this->belongsTo(Product::class); }
}
