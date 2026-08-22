<?php

namespace Modules\Hospitality\Models;

use App\Models\PosOrder;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantOrder extends HospitalityModel
{
    protected $table = 'hospitality_restaurant_orders';

    protected $fillable = [
        'tenant_id',
        'business_id',
        'reservation_id',
        'guest_profile_id',
        'pos_order_id',
        'restaurant_table_id',
        'table_number',
        'reserved_for',
        'party_size',
        'order_type',
        'waiter_id',
        'payment_method_id',
        'shipping_method',
        'kitchen_status',
        'billing_status',
        'total',
        'served_at',
        'kitchen_started_at',
        'kitchen_ready_at',
        'kitchen_served_at',
        'notes',
    ];

    protected $casts = [
        'reserved_for' => 'datetime',
        'served_at' => 'datetime',
        'kitchen_started_at' => 'datetime',
        'kitchen_ready_at' => 'datetime',
        'kitchen_served_at' => 'datetime',
        'total' => 'decimal:2',
    ];

    public function posOrder(): BelongsTo
    {
        return $this->belongsTo(PosOrder::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function guestProfile(): BelongsTo
    {
        return $this->belongsTo(GuestProfile::class);
    }

    public function waiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waiter_id');
    }

    public function restaurantTable(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
