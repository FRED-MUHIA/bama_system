<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use BelongsToBusiness;

    public const PRODUCT_TYPES = [
        'simple' => 'Simple Product',
        'variable' => 'Variable Product',
        'service' => 'Service',
        'bundle' => 'Bundle / Kit',
        'composite' => 'Composite Product',
        'digital' => 'Digital Product',
    ];

    protected $fillable = [
        'tenant_id', 'business_id', 'product_category_id', 'product_brand_id', 'product_type',
        'name', 'sku', 'barcode', 'description', 'main_image_path', 'price', 'wholesale_price',
        'promotional_price', 'cost_price', 'tax_class', 'stock_quantity', 'reorder_level',
        'stock_unit', 'is_active', 'status', 'track_inventory', 'is_serialized',
        'is_batch_tracked', 'is_expiry_tracked', 'weight', 'length', 'width', 'height',
        'warranty_duration', 'warranty_unit', 'warranty_terms',
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
        'wholesale_price' => 'decimal:2',
        'promotional_price' => 'decimal:2',
        'track_inventory' => 'boolean',
        'is_serialized' => 'boolean',
        'is_batch_tracked' => 'boolean',
        'is_expiry_tracked' => 'boolean',
        'weight' => 'decimal:4',
        'length' => 'decimal:4',
        'width' => 'decimal:4',
        'height' => 'decimal:4',
    ];

    public function category() { return $this->belongsTo(ProductCategory::class, 'product_category_id'); }
    public function brand() { return $this->belongsTo(ProductBrand::class, 'product_brand_id'); }
    public function orderItems() { return $this->hasMany(PosOrderItem::class); }
    public function stockMovements() { return $this->hasMany(StockMovement::class); }
    public function retailProfile() { return $this->hasOne(\Modules\Retail\Models\RetailProductProfile::class); }
    public function retailVariants() { return $this->hasMany(\Modules\Retail\Models\RetailProductVariant::class, 'parent_product_id'); }
    public function variantProfile() { return $this->hasOne(\Modules\Retail\Models\RetailProductVariant::class, 'product_id'); }
    public function retailBundleComponents() { return $this->hasMany(\Modules\Retail\Models\RetailProductBundle::class, 'bundle_product_id'); }
    public function retailInventoryBalances() { return $this->hasMany(\Modules\Retail\Models\RetailInventoryBalance::class); }
    public function retailBatches() { return $this->hasMany(\Modules\Retail\Models\ProductBatch::class); }
    public function scanEvents() { return $this->hasMany(\Modules\Retail\Models\ScanEvent::class); }
    public function attributeAssignments() { return $this->hasMany(ProductAttributeAssignment::class)->orderBy('sort_order'); }
    public function attributes() { return $this->belongsToMany(ProductAttribute::class, 'product_attribute_assignments')->withPivot(['is_variant_attribute', 'sort_order', 'metadata'])->withTimestamps(); }
    public function packagingUnits() { return $this->hasMany(ProductPackagingUnit::class); }
    public function supplierItems() { return $this->hasMany(ProductSupplierItem::class); }
    public function serials() { return $this->hasMany(\Modules\Retail\Models\ProductSerial::class); }

    public function isLowStock(): bool
    {
        return (float) $this->reorder_level > 0 && (float) $this->stock_quantity <= (float) $this->reorder_level;
    }

    public function stockUnitLabel(): string
    {
        return self::STOCK_UNITS[$this->stock_unit ?: 'pcs'] ?? strtoupper((string) $this->stock_unit);
    }

    public function productTypeLabel(): string
    {
        return self::PRODUCT_TYPES[$this->product_type ?: 'simple'] ?? str((string) $this->product_type)->headline()->toString();
    }

    public function formattedStock(?float $quantity = null): string
    {
        $quantity ??= (float) $this->stock_quantity;

        return rtrim(rtrim(number_format($quantity, 3), '0'), '.').' '.($this->stock_unit ?: 'pcs');
    }
}
