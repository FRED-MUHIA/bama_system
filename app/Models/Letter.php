<?php

namespace App\Models;

use App\Casts\TolerantEncryptedString;
use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class Letter extends Model
{
    use BelongsToBusiness;

    public const STATUSES = ['Draft', 'Pending', 'Approved', 'Sent', 'Archived'];
    public const TYPES = ['Financial', 'Project', 'Legal', 'Warranty', 'General', 'Procurement', 'Commercial', 'Technical'];

    protected $fillable = [
        'business_id', 'letter_template_id', 'letter_number', 'prefix', 'client_id', 'site_id', 'project_id',
        'invoice_id', 'receipt_id', 'payment_id', 'warranty_id', 'type', 'subject', 'content', 'content_type',
        'status', 'created_by', 'approved_by', 'approved_at', 'recipient', 'sent_at', 'portal_published_at', 'delivery_status',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'sent_at' => 'datetime',
        'portal_published_at' => 'datetime',
        'content' => TolerantEncryptedString::class,
        'recipient' => TolerantEncryptedString::class,
    ];

    public function template() { return $this->belongsTo(LetterTemplate::class, 'letter_template_id'); }
    public function client() { return $this->belongsTo(Client::class); }
    public function site() { return $this->belongsTo(Site::class); }
    public function project() { return $this->belongsTo(Project::class); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function receipt() { return $this->belongsTo(Receipt::class); }
    public function payment() { return $this->belongsTo(Payment::class); }
    public function warranty() { return $this->belongsTo(Warranty::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function attachments() { return $this->hasMany(LetterAttachment::class); }
    public function versions() { return $this->hasMany(LetterVersion::class); }
}
