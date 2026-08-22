<?php

namespace Shared\Compliance\Etims\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class EtimsAuditLog extends Model
{
    use BelongsToBusiness;

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
    ];

    public function submission()
    {
        return $this->belongsTo(EtimsSubmission::class, 'etims_submission_id');
    }
}
