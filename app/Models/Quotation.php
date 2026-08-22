<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'client_id', 'site_id', 'project_id', 'contact_id', 'quotation_number', 'quotation_date', 'valid_until', 'status',
        'subtotal', 'discount_total', 'tax_total', 'total', 'terms', 'notes', 'sent_at',
    ];

    protected $casts = ['quotation_date' => 'date', 'valid_until' => 'date', 'sent_at' => 'datetime'];

    public function client() { return $this->belongsTo(Client::class); }
    public function site() { return $this->belongsTo(Site::class); }
    public function project() { return $this->belongsTo(Project::class); }
    public function contact() { return $this->belongsTo(Contact::class); }
    public function items() { return $this->hasMany(QuotationItem::class); }
    public function invoice() { return $this->hasOne(Invoice::class); }
    public function emailLogs() { return $this->morphMany(EmailLog::class, 'emailable'); }

    public static function supportsProjectLinks(): bool
    {
        return \Illuminate\Support\Facades\Schema::hasColumn('quotations', 'project_id')
            && \Illuminate\Support\Facades\Schema::hasColumn('quotations', 'site_id');
    }
}
