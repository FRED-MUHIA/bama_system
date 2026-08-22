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
        $this->tables();
        $this->registerScanning();
    }

    public function down(): void
    {
        foreach ([
            'camera_scan_events',
            'self_checkout_transactions',
            'scan_audit_logs',
            'product_verification',
            'product_expiry',
            'product_batch_movements',
            'product_batches',
            'scan_events',
            'scan_devices',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function tables(): void
    {
        if (! Schema::hasTable('scan_devices')) {
            Schema::create('scan_devices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('retail_warehouse_id')->nullable()->constrained('retail_warehouses')->nullOnDelete();
                $table->string('device_code');
                $table->string('name');
                $table->string('device_type')->default('POS Scanner');
                $table->string('register_number')->nullable();
                $table->string('status')->default('Active');
                $table->json('capabilities')->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'device_code']);
                $table->index(['business_id', 'branch_id']);
            });
        }

        if (! Schema::hasTable('scan_events')) {
            Schema::create('scan_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('scan_device_id')->nullable()->constrained('scan_devices')->nullOnDelete();
                $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('pos_order_id')->nullable()->constrained('pos_orders')->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('retail_warehouse_id')->nullable()->constrained('retail_warehouses')->nullOnDelete();
                $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('input_type');
                $table->string('symbology')->nullable();
                $table->string('raw_value')->nullable();
                $table->string('identifier_type')->nullable();
                $table->string('identifier_value')->nullable();
                $table->string('result')->default('Success');
                $table->string('message')->nullable();
                $table->decimal('quantity', 14, 3)->default(1);
                $table->decimal('before_quantity', 14, 3)->nullable();
                $table->decimal('sold_quantity', 14, 3)->default(0);
                $table->decimal('remaining_quantity', 14, 3)->nullable();
                $table->decimal('original_price', 14, 2)->default(0);
                $table->decimal('discount', 14, 2)->default(0);
                $table->decimal('final_price', 14, 2)->default(0);
                $table->json('decoded_payload')->nullable();
                $table->json('promotion_payload')->nullable();
                $table->json('compliance_payload')->nullable();
                $table->timestamp('scanned_at')->nullable();
                $table->timestamps();
                $table->index(['business_id', 'result', 'scanned_at']);
                $table->index(['business_id', 'identifier_value']);
            });
        }

        if (! Schema::hasTable('product_batches')) {
            Schema::create('product_batches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('retail_warehouse_id')->nullable()->constrained('retail_warehouses')->nullOnDelete();
                $table->string('batch_number');
                $table->date('manufacture_date')->nullable();
                $table->date('expiry_date')->nullable();
                $table->string('serial_number')->nullable();
                $table->string('supplier_reference')->nullable();
                $table->decimal('quantity', 14, 3)->default(0);
                $table->decimal('reserved_quantity', 14, 3)->default(0);
                $table->decimal('sold_quantity', 14, 3)->default(0);
                $table->string('recall_status')->default('Clear');
                $table->string('compliance_status')->default('Compliant');
                $table->string('status')->default('Active');
                $table->text('recall_reason')->nullable();
                $table->timestamp('recalled_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'product_id', 'batch_number']);
                $table->index(['business_id', 'expiry_date', 'status']);
            });
        }

        if (! Schema::hasTable('product_batch_movements')) {
            Schema::create('product_batch_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_batch_id')->constrained('product_batches')->cascadeOnDelete();
                $table->foreignId('scan_event_id')->nullable()->constrained('scan_events')->nullOnDelete();
                $table->string('type');
                $table->decimal('quantity', 14, 3);
                $table->decimal('balance_after', 14, 3)->default(0);
                $table->string('reference')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('product_expiry')) {
            Schema::create('product_expiry', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_batch_id')->nullable()->constrained('product_batches')->nullOnDelete();
                $table->date('manufacture_date')->nullable();
                $table->date('expiry_date')->nullable();
                $table->unsignedInteger('warning_days')->default(30);
                $table->string('status')->default('Valid');
                $table->timestamp('checked_at')->nullable();
                $table->timestamps();
                $table->index(['business_id', 'expiry_date', 'status']);
            });
        }

        if (! Schema::hasTable('product_verification')) {
            Schema::create('product_verification', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('scan_event_id')->nullable()->constrained('scan_events')->nullOnDelete();
                $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('product_batch_id')->nullable()->constrained('product_batches')->nullOnDelete();
                $table->string('identifier_type')->nullable();
                $table->string('identifier_value')->nullable();
                $table->string('verification_result');
                $table->string('risk_level')->default('Low');
                $table->boolean('product_exists')->default(false);
                $table->boolean('product_active')->default(false);
                $table->boolean('batch_valid')->default(false);
                $table->boolean('not_recalled')->default(true);
                $table->boolean('fraud_suspected')->default(false);
                $table->json('checks')->nullable();
                $table->text('message')->nullable();
                $table->timestamps();
                $table->index(['business_id', 'verification_result', 'risk_level'], 'product_verification_result_risk_idx');
            });
        }

        if (! Schema::hasTable('scan_audit_logs')) {
            Schema::create('scan_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('scan_event_id')->nullable()->constrained('scan_events')->nullOnDelete();
                $table->foreignId('scan_device_id')->nullable()->constrained('scan_devices')->nullOnDelete();
                $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->string('event');
                $table->string('result');
                $table->string('device_code')->nullable();
                $table->ipAddress('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();
                $table->index(['business_id', 'event', 'result']);
            });
        }

        if (! Schema::hasTable('self_checkout_transactions')) {
            Schema::create('self_checkout_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('scan_device_id')->nullable()->constrained('scan_devices')->nullOnDelete();
                $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('pos_order_id')->nullable()->constrained('pos_orders')->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->string('checkout_number');
                $table->string('status')->default('Open');
                $table->decimal('subtotal', 14, 2)->default(0);
                $table->decimal('discount_total', 14, 2)->default(0);
                $table->decimal('tax_total', 14, 2)->default(0);
                $table->decimal('total', 14, 2)->default(0);
                $table->string('payment_status')->default('Pending');
                $table->string('payment_method')->nullable();
                $table->string('receipt_channel')->nullable();
                $table->json('cart_payload')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'checkout_number']);
            });
        }

        if (! Schema::hasTable('camera_scan_events')) {
            Schema::create('camera_scan_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('scan_event_id')->nullable()->constrained('scan_events')->nullOnDelete();
                $table->foreignId('scan_device_id')->nullable()->constrained('scan_devices')->nullOnDelete();
                $table->string('camera_type')->default('POS Camera');
                $table->string('image_path')->nullable();
                $table->string('detection_result')->default('Pending');
                $table->json('detected_codes')->nullable();
                $table->json('detected_products')->nullable();
                $table->decimal('confidence', 5, 2)->default(0);
                $table->text('message')->nullable();
                $table->timestamps();
                $table->index(['business_id', 'detection_result']);
            });
        }
    }

    private function registerScanning(): void
    {
        $now = now();
        $permissions = [
            'retail.scanning.view',
            'retail.scanning.manage',
            'retail.scanning.self-checkout',
            'retail.scanning.reports',
            'retail.scanning.override',
            'retail.scanning.compliance',
        ];

        if (Schema::hasTable('iam_permissions')) {
            foreach ($permissions as $permission) {
                DB::table('iam_permissions')->updateOrInsert(
                    ['name' => $permission],
                    ['module' => 'retail', 'description' => Str::headline(str_replace(['retail.', '.', '-'], ['', ' ', ' '], $permission)), 'updated_at' => $now, 'created_at' => $now]
                );
            }
        }

        if (Schema::hasTable('dashboard_widgets')) {
            foreach ([
                ['retail-smart-scans-total', 'Smart Scans Total', 'retail.scanning.view'],
                ['retail-smart-scans-failed', 'Smart Scan Failures', 'retail.scanning.view'],
                ['retail-expired-products-blocked', 'Expired Products Blocked', 'retail.scanning.compliance'],
                ['retail-fraud-attempts', 'Fraud Attempts', 'retail.scanning.compliance'],
            ] as [$slug, $name, $permission]) {
                DB::table('dashboard_widgets')->updateOrInsert(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'module_slug' => 'retail',
                        'industry' => 'retail',
                        'component' => 'retail.scanning.widgets.metric-card',
                        'permission' => $permission,
                        'settings_schema' => json_encode(['supports_branch_filters' => true, 'supports_device_filters' => true]),
                        'is_active' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }
    }
};
