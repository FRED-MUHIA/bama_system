<?php

namespace App\Models;

use App\Casts\TolerantEncryptedString;
use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'client_id', 'site_name', 'address', 'notes'];

    protected function casts(): array
    {
        return [
            'address' => TolerantEncryptedString::class,
            'notes' => TolerantEncryptedString::class,
        ];
    }

    public function client() { return $this->belongsTo(Client::class); }
    public function projects() { return $this->hasMany(Project::class); }
    public function invoices() { return $this->hasMany(Invoice::class); }
    public function quotations() { return $this->hasMany(Quotation::class); }
    public function letters() { return $this->hasMany(Letter::class); }
}
