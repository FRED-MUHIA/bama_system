<?php

namespace Modules\PrintingBranding\Models;

use App\Models\Client;
use App\Models\User;

class Artwork extends PrintingBrandingModel
{
    protected $table = 'printing_artworks';

    protected $casts = ['uploaded_at' => 'datetime'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function job()
    {
        return $this->belongsTo(ProductionJob::class, 'job_id');
    }

    public function designer()
    {
        return $this->belongsTo(User::class, 'designer_id');
    }

    public function approvals()
    {
        return $this->hasMany(ProofApproval::class, 'artwork_id');
    }
}
