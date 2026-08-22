<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Client extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'type', 'name', 'phone', 'email', 'company_name', 'address', 'billing_address', 'kra_pin', 'notes',
    ];

    public function quotations() { return $this->hasMany(Quotation::class); }
    public function invoices() { return $this->hasMany(Invoice::class); }
    public function receipts() { return $this->hasManyThrough(Receipt::class, Invoice::class); }
    public function contacts() { return $this->hasMany(Contact::class); }
    public function sites() { return $this->hasMany(Site::class); }
    public function projects() { return $this->hasMany(Project::class); }
    public function posOrders() { return $this->hasMany(PosOrder::class); }
    public function primaryContact() { return $this->hasOne(Contact::class)->where('is_primary', true); }
    public function letters() { return $this->hasMany(Letter::class); }
    public function retailProfile() { return $this->hasOne(\Modules\Retail\Models\RetailCustomerProfile::class); }
    public function retailLoyaltyAccount() { return $this->hasOne(\Modules\Retail\Models\RetailLoyaltyAccount::class); }

    public static function supportsCompanyStructure(): bool
    {
        return Schema::hasColumn('clients', 'type')
            && Schema::hasTable('contacts')
            && Schema::hasTable('sites')
            && Schema::hasTable('projects');
    }
}
