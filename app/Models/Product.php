<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'product_category_id', 'name', 'sku', 'description', 'price',
        'cost_price', 'stock_quantity', 'reorder_level', 'stock_unit', 'is_active',
    ];

    public const STOCK_UNITS = [
        'pcs' => 'Pieces',
        'kg' => 'Kilograms',
        'g' => 'Grams',
        'l' => 'Litres',
        'ml' => 'Millilitres',
        'box' => 'Boxes',
        'carton' => 'Cartons',
        'pack' => 'Packs',
        'bottle' => 'Bottles',
        'tray' => 'Trays',
        'bag' => 'Bags',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock_quantity' => 'decimal:3',
        'reorder_level' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    public function category() { return $this->belongsTo(ProductCategory::class, 'product_category_id'); }
    public function orderItems() { return $this->hasMany(PosOrderItem::class); }
    public function stockMovements() { return $this->hasMany(StockMovement::class); }
    public function retailProfile() { return $this->hasOne(\Modules\Retail\Models\RetailProductProfile::class); }
    public function retailVariants() { return $this->hasMany(\Modules\Retail\Models\RetailProductVariant::class, 'parent_product_id'); }
    public function retailBundleComponents() { return $this->hasMany(\Modules\Retail\Models\RetailProductBundle::class, 'bundle_product_id'); }
    public function retailInventoryBalances() { return $this->hasMany(\Modules\Retail\Models\RetailInventoryBalance::class); }
    public function retailBatches() { return $this->hasMany(\Modules\Retail\Models\ProductBatch::class); }
    public function scanEvents() { return $this->hasMany(\Modules\Retail\Models\ScanEvent::class); }

    public function isLowStock(): bool
    {
        return (float) $this->reorder_level > 0 && (float) $this->stock_quantity <= (float) $this->reorder_level;
    }

    public function stockUnitLabel(): string
    {
        return self::STOCK_UNITS[$this->stock_unit ?: 'pcs'] ?? strtoupper((string) $this->stock_unit);
    }

    public function formattedStock(?float $quantity = null): string
    {
        $quantity ??= (float) $this->stock_quantity;

        return rtrim(rtrim(number_format($quantity, 3), '0'), '.').' '.($this->stock_unit ?: 'pcs');
    }
}
