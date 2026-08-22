<?php

namespace Modules\Automotive\Models;

use App\Models\Product;

class PartRequestItem extends AutomotiveModel
{
    protected $table = 'automotive_part_request_items';

    public function request() { return $this->belongsTo(PartRequest::class, 'part_request_id'); }
    public function part() { return $this->belongsTo(Part::class); }
    public function product() { return $this->belongsTo(Product::class); }
}
