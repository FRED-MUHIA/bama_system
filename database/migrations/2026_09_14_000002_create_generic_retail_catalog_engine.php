<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $this->enhanceCategories();
        $this->createCatalogTables();
        $this->enhanceProducts();
        $this->enhanceRetailVariantTables();
        $this->enhanceExactVariantTransactionTables();
        $this->registerPermissions();
    }

    public function down(): void
    {
        $this->dropExactVariantTransactionColumns();
        $this->dropRetailVariantColumns();
        $this->dropProductColumns();
        $this->dropCategoryColumns();

        foreach ([
            'product_supplier_items',
            'product_serials',
            'product_packaging_units',
            'product_units',
            'product_variant_attribute_values',
            'product_attribute_assignments',
            'category_attributes',
            'product_attribute_values',
            'product_attributes',
            'product_brands',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function enhanceCategories(): void
    {
        if (! Schema::hasTable('product_categories')) {
            return;
        }

        Schema::table('product_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('product_categories', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('business_id')->constrained('product_categories')->nullOnDelete();
            }
            if (! Schema::hasColumn('product_categories', 'code')) {
                $table->string('code')->nullable()->after('name');
            }
            if (! Schema::hasColumn('product_categories', 'slug')) {
                $table->string('slug')->nullable()->after('code');
            }
            if (! Schema::hasColumn('product_categories', 'image_path')) {
                $table->string('image_path')->nullable()->after('description');
            }
            if (! Schema::hasColumn('product_categories', 'icon')) {
                $table->string('icon')->nullable()->after('image_path');
            }
            if (! Schema::hasColumn('product_categories', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('icon');
            }
            if (! Schema::hasColumn('product_categories', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('sort_order');
            }
        });

        Schema::table('product_categories', function (Blueprint $table) {
            if (! Schema::hasIndex('product_categories', 'product_categories_business_parent_sort_idx')) {
                $table->index(['business_id', 'parent_id', 'sort_order'], 'product_categories_business_parent_sort_idx');
            }
            if (! Schema::hasIndex('product_categories', 'product_categories_business_slug_idx')) {
                $table->index(['business_id', 'slug'], 'product_categories_business_slug_idx');
            }
            if (! Schema::hasIndex('product_categories', 'product_categories_business_code_idx')) {
                $table->index(['business_id', 'code'], 'product_categories_business_code_idx');
            }
        });
    }

    private function createCatalogTables(): void
    {
        if (! Schema::hasTable('product_brands')) {
            Schema::create('product_brands', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('code')->nullable();
                $table->string('slug')->nullable();
                $table->string('logo_path')->nullable();
                $table->text('description')->nullable();
                $table->string('website_url')->nullable();
                $table->string('status')->default('Active');
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['business_id', 'name'], 'product_brands_business_name_unique');
                $table->index(['business_id', 'code'], 'product_brands_business_code_idx');
                $table->index(['business_id', 'slug'], 'product_brands_business_slug_idx');
                $table->index(['business_id', 'status']);
            });
        }

        if (! Schema::hasTable('product_attributes')) {
            Schema::create('product_attributes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('code');
                $table->string('display_type')->default('Dropdown');
                $table->string('unit')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_variant_attribute')->default(false);
                $table->boolean('is_filterable')->default(false);
                $table->boolean('is_searchable')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['business_id', 'code'], 'product_attributes_business_code_unique');
                $table->index(['business_id', 'is_variant_attribute', 'is_active'], 'product_attributes_variant_active_idx');
                $table->index(['business_id', 'is_filterable', 'is_active'], 'product_attributes_filter_active_idx');
            });
        }

        if (! Schema::hasTable('product_attribute_values')) {
            Schema::create('product_attribute_values', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_attribute_id')->constrained('product_attributes')->cascadeOnDelete();
                $table->string('value');
                $table->string('code')->nullable();
                $table->decimal('numeric_value', 14, 4)->nullable();
                $table->string('unit')->nullable();
                $table->string('hex_color', 20)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['product_attribute_id', 'value'], 'attribute_value_unique');
                $table->index(['business_id', 'product_attribute_id', 'sort_order'], 'attribute_values_sort_idx');
                $table->index(['business_id', 'code'], 'attribute_values_code_idx');
            });
        }

        if (! Schema::hasTable('category_attributes')) {
            Schema::create('category_attributes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_category_id')->constrained('product_categories')->cascadeOnDelete();
                $table->foreignId('product_attribute_id')->constrained('product_attributes')->cascadeOnDelete();
                $table->boolean('is_required')->default(false);
                $table->boolean('is_variant_attribute')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['product_category_id', 'product_attribute_id'], 'category_attribute_unique');
                $table->index(['business_id', 'product_category_id', 'sort_order'], 'category_attributes_sort_idx');
            });
        }

        if (! Schema::hasTable('product_attribute_assignments')) {
            Schema::create('product_attribute_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_attribute_id')->constrained('product_attributes')->cascadeOnDelete();
                $table->boolean('is_variant_attribute')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['product_id', 'product_attribute_id'], 'product_attribute_assignment_unique');
                $table->index(['business_id', 'product_id', 'sort_order'], 'product_attribute_assignments_sort_idx');
            });
        }

        if (! Schema::hasTable('product_variant_attribute_values')) {
            Schema::create('product_variant_attribute_values', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('variant_id')->constrained('retail_product_variants')->cascadeOnDelete();
                $table->foreignId('product_attribute_id')->constrained('product_attributes')->cascadeOnDelete();
                $table->foreignId('product_attribute_value_id')->constrained('product_attribute_values')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['variant_id', 'product_attribute_id'], 'variant_attribute_unique');
                $table->index(['business_id', 'product_attribute_id', 'product_attribute_value_id'], 'variant_attribute_values_lookup_idx');
            });
        }

        if (! Schema::hasTable('product_units')) {
            Schema::create('product_units', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('code');
                $table->string('unit_type')->default('count');
                $table->decimal('base_ratio', 18, 6)->default(1);
                $table->boolean('is_base')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['business_id', 'code'], 'product_units_business_code_unique');
                $table->index(['business_id', 'unit_type', 'is_active']);
            });
        }

        if (! Schema::hasTable('product_packaging_units')) {
            Schema::create('product_packaging_units', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_unit_id')->nullable()->constrained('product_units')->nullOnDelete();
                $table->string('name');
                $table->string('code')->nullable();
                $table->decimal('conversion_ratio', 18, 6)->default(1);
                $table->string('barcode')->nullable();
                $table->decimal('price', 14, 2)->nullable();
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['business_id', 'product_id', 'name'], 'product_packaging_unit_unique');
                $table->index(['business_id', 'barcode']);
            });
        }

        if (! Schema::hasTable('product_serials')) {
            Schema::create('product_serials', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('retail_product_variant_id')->nullable()->constrained('retail_product_variants')->nullOnDelete();
                $table->foreignId('product_batch_id')->nullable()->constrained('product_batches')->nullOnDelete();
                $table->foreignId('pos_order_id')->nullable()->constrained('pos_orders')->nullOnDelete();
                $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
                $table->string('serial_number');
                $table->string('imei')->nullable();
                $table->string('asset_identifier')->nullable();
                $table->string('status')->default('In Stock');
                $table->date('warranty_starts_at')->nullable();
                $table->date('warranty_ends_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['business_id', 'serial_number'], 'product_serials_business_serial_unique');
                $table->index(['business_id', 'imei']);
                $table->index(['business_id', 'product_id', 'retail_product_variant_id'], 'product_serials_item_idx');
            });
        }

        if (! Schema::hasTable('product_supplier_items')) {
            Schema::create('product_supplier_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('retail_product_variant_id')->nullable()->constrained('retail_product_variants')->nullOnDelete();
                $table->string('supplier_sku')->nullable();
                $table->string('supplier_product_name')->nullable();
                $table->decimal('supplier_cost', 14, 2)->default(0);
                $table->unsignedInteger('lead_time_days')->default(0);
                $table->decimal('minimum_order_quantity', 14, 3)->default(0);
                $table->string('status')->default('Active');
                $table->timestamps();

                $table->unique(['supplier_id', 'product_id', 'retail_product_variant_id'], 'supplier_product_variant_unique');
                $table->index(['business_id', 'supplier_sku']);
            });
        }
    }

    private function enhanceProducts(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'product_brand_id')) {
                $table->foreignId('product_brand_id')->nullable()->after('product_category_id')->constrained('product_brands')->nullOnDelete();
            }
            if (! Schema::hasColumn('products', 'product_type')) {
                $table->string('product_type')->default('simple')->after('product_brand_id');
            }
            if (! Schema::hasColumn('products', 'barcode')) {
                $table->string('barcode')->nullable()->after('sku');
            }
            if (! Schema::hasColumn('products', 'wholesale_price')) {
                $table->decimal('wholesale_price', 14, 2)->nullable()->after('price');
            }
            if (! Schema::hasColumn('products', 'promotional_price')) {
                $table->decimal('promotional_price', 14, 2)->nullable()->after('wholesale_price');
            }
            if (! Schema::hasColumn('products', 'tax_class')) {
                $table->string('tax_class')->nullable()->after('promotional_price');
            }
            if (! Schema::hasColumn('products', 'main_image_path')) {
                $table->string('main_image_path')->nullable()->after('description');
            }
            if (! Schema::hasColumn('products', 'status')) {
                $table->string('status')->default('active')->after('is_active');
            }
            if (! Schema::hasColumn('products', 'track_inventory')) {
                $table->boolean('track_inventory')->default(true)->after('status');
            }
            if (! Schema::hasColumn('products', 'is_serialized')) {
                $table->boolean('is_serialized')->default(false)->after('track_inventory');
            }
            if (! Schema::hasColumn('products', 'is_batch_tracked')) {
                $table->boolean('is_batch_tracked')->default(false)->after('is_serialized');
            }
            if (! Schema::hasColumn('products', 'is_expiry_tracked')) {
                $table->boolean('is_expiry_tracked')->default(false)->after('is_batch_tracked');
            }
            if (! Schema::hasColumn('products', 'weight')) {
                $table->decimal('weight', 14, 4)->nullable()->after('is_expiry_tracked');
            }
            if (! Schema::hasColumn('products', 'length')) {
                $table->decimal('length', 14, 4)->nullable()->after('weight');
            }
            if (! Schema::hasColumn('products', 'width')) {
                $table->decimal('width', 14, 4)->nullable()->after('length');
            }
            if (! Schema::hasColumn('products', 'height')) {
                $table->decimal('height', 14, 4)->nullable()->after('width');
            }
            if (! Schema::hasColumn('products', 'warranty_duration')) {
                $table->unsignedInteger('warranty_duration')->nullable()->after('height');
            }
            if (! Schema::hasColumn('products', 'warranty_unit')) {
                $table->string('warranty_unit')->nullable()->after('warranty_duration');
            }
            if (! Schema::hasColumn('products', 'warranty_terms')) {
                $table->text('warranty_terms')->nullable()->after('warranty_unit');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasIndex('products', 'products_business_brand_idx')) {
                $table->index(['business_id', 'product_brand_id'], 'products_business_brand_idx');
            }
            if (! Schema::hasIndex('products', 'products_business_barcode_idx')) {
                $table->index(['business_id', 'barcode'], 'products_business_barcode_idx');
            }
            if (! Schema::hasIndex('products', 'products_business_type_status_idx')) {
                $table->index(['business_id', 'product_type', 'status'], 'products_business_type_status_idx');
            }
        });

        if (Schema::hasTable('retail_product_profiles')) {
            Schema::table('retail_product_profiles', function (Blueprint $table) {
                if (! Schema::hasColumn('retail_product_profiles', 'product_brand_id')) {
                    $table->foreignId('product_brand_id')->nullable()->after('supplier_id')->constrained('product_brands')->nullOnDelete();
                }
            });
        }
    }

    private function enhanceRetailVariantTables(): void
    {
        if (Schema::hasTable('retail_product_variants')) {
            Schema::table('retail_product_variants', function (Blueprint $table) {
                if (! Schema::hasColumn('retail_product_variants', 'variant_name')) {
                    $table->string('variant_name')->nullable()->after('product_id');
                }
                if (! Schema::hasColumn('retail_product_variants', 'combination_key')) {
                    $table->string('combination_key')->nullable()->after('barcode');
                }
                if (! Schema::hasColumn('retail_product_variants', 'cost_price')) {
                    $table->decimal('cost_price', 14, 2)->nullable()->after('price_delta');
                }
                if (! Schema::hasColumn('retail_product_variants', 'retail_price')) {
                    $table->decimal('retail_price', 14, 2)->nullable()->after('cost_price');
                }
                if (! Schema::hasColumn('retail_product_variants', 'wholesale_price')) {
                    $table->decimal('wholesale_price', 14, 2)->nullable()->after('retail_price');
                }
                if (! Schema::hasColumn('retail_product_variants', 'promotional_price')) {
                    $table->decimal('promotional_price', 14, 2)->nullable()->after('wholesale_price');
                }
                if (! Schema::hasColumn('retail_product_variants', 'tax_class')) {
                    $table->string('tax_class')->nullable()->after('promotional_price');
                }
                if (! Schema::hasColumn('retail_product_variants', 'weight')) {
                    $table->decimal('weight', 14, 4)->nullable()->after('tax_class');
                }
                if (! Schema::hasColumn('retail_product_variants', 'length')) {
                    $table->decimal('length', 14, 4)->nullable()->after('weight');
                }
                if (! Schema::hasColumn('retail_product_variants', 'width')) {
                    $table->decimal('width', 14, 4)->nullable()->after('length');
                }
                if (! Schema::hasColumn('retail_product_variants', 'height')) {
                    $table->decimal('height', 14, 4)->nullable()->after('width');
                }
                if (! Schema::hasColumn('retail_product_variants', 'image_path')) {
                    $table->string('image_path')->nullable()->after('height');
                }
                if (! Schema::hasColumn('retail_product_variants', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('status');
                }
                if (! Schema::hasColumn('retail_product_variants', 'track_inventory')) {
                    $table->boolean('track_inventory')->default(true)->after('is_active');
                }
            });

            Schema::table('retail_product_variants', function (Blueprint $table) {
                if (! Schema::hasIndex('retail_product_variants', 'retail_variant_combination_unique')) {
                    $table->unique(['business_id', 'parent_product_id', 'combination_key'], 'retail_variant_combination_unique');
                }
                if (! Schema::hasIndex('retail_product_variants', 'retail_variant_sku_idx')) {
                    $table->index(['business_id', 'sku'], 'retail_variant_sku_idx');
                }
                if (! Schema::hasIndex('retail_product_variants', 'retail_variant_barcode_idx')) {
                    $table->index(['business_id', 'barcode'], 'retail_variant_barcode_idx');
                }
            });
        }

        foreach (['product_batches', 'product_expiry', 'scan_events', 'product_verification'] as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'retail_product_variant_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('retail_product_variant_id')->nullable()->after('product_id')->constrained('retail_product_variants')->nullOnDelete();
            });
        }
    }

    private function enhanceExactVariantTransactionTables(): void
    {
        foreach (['retail_inventory_balances', 'retail_inventory_movements', 'retail_return_items', 'retail_order_items', 'pos_order_items', 'purchase_orders', 'goods_received_notes'] as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'retail_product_variant_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $after = Schema::hasColumn($tableName, 'product_id') ? 'product_id' : 'id';
                $table->foreignId('retail_product_variant_id')->nullable()->after($after)->constrained('retail_product_variants')->nullOnDelete();
            });
        }

        if (Schema::hasTable('pos_order_items')) {
            Schema::table('pos_order_items', function (Blueprint $table) {
                if (! Schema::hasColumn('pos_order_items', 'variant_description')) {
                    $table->string('variant_description')->nullable()->after('description');
                }
                if (! Schema::hasColumn('pos_order_items', 'sku_snapshot')) {
                    $table->string('sku_snapshot')->nullable()->after('variant_description');
                }
                if (! Schema::hasColumn('pos_order_items', 'barcode_snapshot')) {
                    $table->string('barcode_snapshot')->nullable()->after('sku_snapshot');
                }
            });
        }

        if (Schema::hasTable('retail_inventory_balances') && Schema::hasColumn('retail_inventory_balances', 'retail_product_variant_id')) {
            Schema::table('retail_inventory_balances', function (Blueprint $table) {
                if (Schema::hasIndex('retail_inventory_balances', 'retail_inventory_balance_unique')) {
                    $table->dropUnique('retail_inventory_balance_unique');
                }
                if (! Schema::hasIndex('retail_inventory_balances', 'retail_inventory_balance_variant_unique')) {
                    $table->unique(
                        ['business_id', 'product_id', 'retail_product_variant_id', 'branch_id', 'retail_warehouse_id', 'retail_warehouse_bin_id'],
                        'retail_inventory_balance_variant_unique'
                    );
                }
            });
        }
    }

    private function registerPermissions(): void
    {
        if (! Schema::hasTable('iam_permissions')) {
            return;
        }

        $permissions = [
            'retail.categories.view',
            'retail.categories.manage',
            'retail.brands.view',
            'retail.brands.manage',
            'retail.attributes.view',
            'retail.attributes.manage',
            'retail.variants.view',
            'retail.variants.manage',
            'retail.pricing.manage',
            'retail.inventory.adjust',
            'retail.serials.manage',
            'retail.batches.manage',
        ];

        foreach ($permissions as $permission) {
            DB::table('iam_permissions')->updateOrInsert(
                ['name' => $permission],
                [
                    'module' => 'retail',
                    'description' => Str::headline(str_replace(['retail.', '.', '-'], ['', ' ', ' '], $permission)),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    private function dropExactVariantTransactionColumns(): void
    {
        if (Schema::hasTable('retail_inventory_balances') && Schema::hasColumn('retail_inventory_balances', 'retail_product_variant_id')) {
            Schema::table('retail_inventory_balances', function (Blueprint $table) {
                if (Schema::hasIndex('retail_inventory_balances', 'retail_inventory_balance_variant_unique')) {
                    $table->dropUnique('retail_inventory_balance_variant_unique');
                }
                $table->dropConstrainedForeignId('retail_product_variant_id');
            });
        }

        foreach (['retail_inventory_movements', 'retail_return_items', 'retail_order_items', 'purchase_orders', 'goods_received_notes'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'retail_product_variant_id')) {
                Schema::table($tableName, fn (Blueprint $table) => $table->dropConstrainedForeignId('retail_product_variant_id'));
            }
        }

        if (Schema::hasTable('pos_order_items')) {
            Schema::table('pos_order_items', function (Blueprint $table) {
                if (Schema::hasColumn('pos_order_items', 'retail_product_variant_id')) {
                    $table->dropConstrainedForeignId('retail_product_variant_id');
                }
                foreach (['variant_description', 'sku_snapshot', 'barcode_snapshot'] as $column) {
                    if (Schema::hasColumn('pos_order_items', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    private function dropRetailVariantColumns(): void
    {
        foreach (['product_batches', 'product_expiry', 'scan_events', 'product_verification'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'retail_product_variant_id')) {
                Schema::table($tableName, fn (Blueprint $table) => $table->dropConstrainedForeignId('retail_product_variant_id'));
            }
        }

        if (Schema::hasTable('retail_product_variants')) {
            Schema::table('retail_product_variants', function (Blueprint $table) {
                if (Schema::hasIndex('retail_product_variants', 'retail_variant_combination_unique')) {
                    $table->dropUnique('retail_variant_combination_unique');
                }
                foreach (['retail_variant_sku_idx', 'retail_variant_barcode_idx'] as $index) {
                    if (Schema::hasIndex('retail_product_variants', $index)) {
                        $table->dropIndex($index);
                    }
                }
            });

            Schema::table('retail_product_variants', function (Blueprint $table) {
                foreach ([
                    'variant_name', 'combination_key', 'cost_price', 'retail_price', 'wholesale_price',
                    'promotional_price', 'tax_class', 'weight', 'length', 'width', 'height',
                    'image_path', 'is_active', 'track_inventory',
                ] as $column) {
                    if (Schema::hasColumn('retail_product_variants', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    private function dropProductColumns(): void
    {
        if (Schema::hasTable('retail_product_profiles') && Schema::hasColumn('retail_product_profiles', 'product_brand_id')) {
            Schema::table('retail_product_profiles', fn (Blueprint $table) => $table->dropConstrainedForeignId('product_brand_id'));
        }

        if (! Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            foreach (['products_business_brand_idx', 'products_business_barcode_idx', 'products_business_type_status_idx'] as $index) {
                if (Schema::hasIndex('products', $index)) {
                    $table->dropIndex($index);
                }
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'product_brand_id')) {
                $table->dropConstrainedForeignId('product_brand_id');
            }

            foreach ([
                'product_type', 'barcode', 'wholesale_price', 'promotional_price', 'tax_class',
                'main_image_path', 'status', 'track_inventory', 'is_serialized', 'is_batch_tracked',
                'is_expiry_tracked', 'weight', 'length', 'width', 'height', 'warranty_duration',
                'warranty_unit', 'warranty_terms',
            ] as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function dropCategoryColumns(): void
    {
        if (! Schema::hasTable('product_categories')) {
            return;
        }

        Schema::table('product_categories', function (Blueprint $table) {
            foreach (['product_categories_business_parent_sort_idx', 'product_categories_business_slug_idx', 'product_categories_business_code_idx'] as $index) {
                if (Schema::hasIndex('product_categories', $index)) {
                    $table->dropIndex($index);
                }
            }
        });

        Schema::table('product_categories', function (Blueprint $table) {
            if (Schema::hasColumn('product_categories', 'parent_id')) {
                $table->dropConstrainedForeignId('parent_id');
            }

            foreach (['code', 'slug', 'image_path', 'icon', 'sort_order', 'is_active'] as $column) {
                if (Schema::hasColumn('product_categories', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
