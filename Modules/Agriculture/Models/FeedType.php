<?php

namespace Modules\Agriculture\Models;

use App\Models\Product;

class FeedType extends AgricultureModel
{
    protected $table = 'agriculture_feed_types';
    protected $casts = ['cost_per_unit' => 'decimal:2', 'is_active' => 'boolean'];

    public function product() { return $this->belongsTo(Product::class); }
}
