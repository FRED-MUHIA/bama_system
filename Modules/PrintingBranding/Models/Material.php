<?php

namespace Modules\PrintingBranding\Models;

use App\Models\Product;
use App\Models\Supplier;

class Material extends PrintingBrandingModel
{
    protected $table = 'printing_materials';

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function lowStock(): bool
    {
        return (float) $this->reorder_level > 0 && (float) $this->stock_quantity <= (float) $this->reorder_level;
    }
}
