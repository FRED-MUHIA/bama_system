<?php

namespace Shared\Compliance\Etims\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class EtimsSubmission extends Model
{
    use BelongsToBusiness;

    public const STATUS_PENDING = 'Pending';
    public const STATUS_OFFLINE = 'Offline Queued';
    public const STATUS_SUBMITTED = 'Submitted';
    public const STATUS_VALIDATED = 'Validated';
    public const STATUS_FAILED = 'Failed';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'validation_result' => 'array',
        'next_retry_at' => 'datetime',
        'submitted_at' => 'datetime',
        'validated_at' => 'datetime',
    ];

    public function source()
    {
        return $this->morphTo();
    }

    public function audits()
    {
        return $this->hasMany(EtimsAuditLog::class);
    }
}
