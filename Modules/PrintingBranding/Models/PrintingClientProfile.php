<?php

namespace Modules\PrintingBranding\Models;

use App\Models\Client;

class PrintingClientProfile extends PrintingBrandingModel
{
    protected $table = 'printing_client_profiles';

    protected $casts = [
        'preferred_products' => 'array',
        'previous_jobs' => 'array',
        'artwork_history' => 'array',
        'credit_hold' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
