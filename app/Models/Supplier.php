<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'name', 'email', 'phone', 'kra_pin', 'address'];

    public function quotes() { return $this->hasMany(SupplierQuote::class); }
    public function purchaseOrders() { return $this->hasMany(PurchaseOrder::class); }
    public function invoices() { return $this->hasMany(SupplierInvoice::class); }
    public function retailProfile() { return $this->hasOne(\Modules\Retail\Models\RetailSupplierProfile::class); }
}
