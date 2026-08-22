<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use App\Models\Concerns\AuditsAccountingChanges;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Invoice extends Model
{
    use BelongsToBusiness, AuditsAccountingChanges;

    private static ?bool $supportsPartPayments = null;

    protected static function booted(): void
    {
        static::created(fn (Invoice $invoice) => app(\App\Services\FinanceService::class)->postInvoice($invoice));
    }

    protected $fillable = [
        'business_id', 'client_id', 'site_id', 'project_id', 'department_id', 'cost_center_id', 'contact_id', 'quotation_id', 'parent_invoice_id', 'part_payment_amount', 'invoice_number', 'public_token',
        'industry_module', 'industry_reference', 'issuer_profile', 'recipient_profile', 'industry_context',
        'invoice_date', 'due_date', 'payment_status', 'subtotal', 'discount_total', 'tax_total', 'total', 'amount_paid', 'balance',
        'terms', 'notes', 'sent_at',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'sent_at' => 'datetime',
        'issuer_profile' => 'array',
        'recipient_profile' => 'array',
        'industry_context' => 'array',
    ];

    public function client() { return $this->belongsTo(Client::class); }
    public function quotation() { return $this->belongsTo(Quotation::class); }
    public function site() { return $this->belongsTo(Site::class); }
    public function project() { return $this->belongsTo(Project::class); }
    public function contact() { return $this->belongsTo(Contact::class); }
    public function parentInvoice() { return $this->belongsTo(Invoice::class, 'parent_invoice_id'); }
    public function partPaymentInvoices() { return $this->hasMany(Invoice::class, 'parent_invoice_id'); }
    public function items() { return $this->hasMany(InvoiceItem::class); }
    public function payments() { return $this->hasMany(Payment::class); }
    public function receipts() { return $this->hasMany(Receipt::class); }
    public function posOrder() { return $this->hasOne(PosOrder::class); }
    public function emailLogs() { return $this->morphMany(EmailLog::class, 'emailable'); }
    public function allocations() { return $this->hasMany(InvoiceAllocation::class); }
    public function sourceAllocations() { return $this->hasMany(InvoiceAllocation::class, 'source_invoice_id'); }
    public function letters() { return $this->hasMany(Letter::class); }

    public static function supportsPartPayments(): bool
    {
        return self::$supportsPartPayments ??= Schema::hasColumn('invoices', 'parent_invoice_id')
            && Schema::hasColumn('invoices', 'part_payment_amount');
    }

    public static function supportsProjectLinks(): bool
    {
        return Schema::hasColumn('invoices', 'project_id') && Schema::hasColumn('invoices', 'site_id');
    }

    public static function supportsInvoiceTypes(): bool
    {
        return Schema::hasColumn('invoices', 'invoice_type');
    }

    public static function supportsAllocations(): bool
    {
        return Schema::hasTable('invoice_allocations');
    }

    public function scopeSource(Builder $query): Builder
    {
        if (self::supportsPartPayments()) {
            $query->whereNull('parent_invoice_id');
        }

        if (self::supportsInvoiceTypes()) {
            $query->where('invoice_type', 'STANDARD');
        }

        return $query;
    }

    public function isPartPayment(): bool
    {
        return self::supportsPartPayments() && $this->parent_invoice_id !== null;
    }

    public function isAllocationInvoice(): bool
    {
        return $this->isPartPayment() || (self::supportsInvoiceTypes() && ($this->invoice_type ?? 'STANDARD') !== 'STANDARD');
    }
}
