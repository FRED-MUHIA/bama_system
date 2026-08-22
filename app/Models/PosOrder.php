<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class PosOrder extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'invoice_id', 'client_id', 'payment_method_id', 'order_number', 'tracking_key', 'order_date', 'customer_name',
        'customer_phone', 'customer_email', 'customer_address', 'customer_type', 'status', 'approved_at', 'subtotal', 'discount_total', 'tax_total', 'custom_amount',
        'total', 'amount_paid', 'notes',
    ];

    protected $casts = ['order_date' => 'date', 'approved_at' => 'datetime'];

    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function paymentMethod() { return $this->belongsTo(PaymentMethod::class); }
    public function items() { return $this->hasMany(PosOrderItem::class); }
    public function payments() { return $this->hasMany(PosOrderPayment::class); }
    public function retailExtension() { return $this->hasOne(\Modules\Retail\Models\RetailSalesExtension::class); }
    public function retailReturns() { return $this->hasMany(\Modules\Retail\Models\RetailReturnAuthorization::class); }
    public function scanEvents() { return $this->hasMany(\Modules\Retail\Models\ScanEvent::class); }
    public function etimsSubmissions() { return $this->morphMany(\Shared\Compliance\Etims\Models\EtimsSubmission::class, 'source'); }
}
