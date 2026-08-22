<?php

namespace Modules\PrintingBranding\Models;

use App\Models\Client;
use App\Models\Product;
use App\Models\Quotation;

class Estimate extends PrintingBrandingModel
{
    protected $table = 'printing_estimates';

    protected $casts = [
        'specifications' => 'array',
        'finishing' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }
}
