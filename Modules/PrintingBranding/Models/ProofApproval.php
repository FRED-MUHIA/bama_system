<?php

namespace Modules\PrintingBranding\Models;

class ProofApproval extends PrintingBrandingModel
{
    protected $table = 'printing_proof_approvals';

    protected $casts = [
        'sent_at' => 'datetime',
        'approval_date' => 'datetime',
        'audit_trail' => 'array',
    ];

    public function artwork()
    {
        return $this->belongsTo(Artwork::class, 'artwork_id');
    }

    public function job()
    {
        return $this->belongsTo(ProductionJob::class, 'job_id');
    }
}
