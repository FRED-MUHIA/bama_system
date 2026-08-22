<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class AccountingAuditLog extends Model
{
    use BelongsToBusiness;
    public $timestamps = false;
    protected $fillable = ['business_id', 'user_id', 'event', 'auditable_type', 'auditable_id', 'old_values', 'new_values', 'created_at'];
    protected $casts = ['old_values' => 'array', 'new_values' => 'array', 'created_at' => 'datetime'];
}
