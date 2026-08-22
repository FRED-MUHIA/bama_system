<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'invoice_id', 'project_id', 'payment_id', 'receipt_number', 'amount_paid',
        'balance_remaining', 'status', 'payment_method', 'payment_date', 'sent_at',
    ];

    protected $casts = ['payment_date' => 'date', 'sent_at' => 'datetime'];

    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function payment() { return $this->belongsTo(Payment::class); }
    public function emailLogs() { return $this->morphMany(EmailLog::class, 'emailable'); }
    public function project() { return $this->belongsTo(Project::class); }
    public function allocations() { return $this->hasMany(ReceiptAllocation::class); }
    public function letters() { return $this->hasMany(Letter::class); }
}
