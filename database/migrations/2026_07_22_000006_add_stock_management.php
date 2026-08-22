<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (! Schema::hasColumn('products', 'reorder_level')) {
                    $table->integer('reorder_level')->default(0)->after('stock_quantity');
                }
            });
        }

        if (Schema::hasTable('goods_received_notes')) {
            Schema::table('goods_received_notes', function (Blueprint $table) {
                if (! Schema::hasColumn('goods_received_notes', 'product_id')) {
                    $table->foreignId('product_id')->nullable()->after('purchase_order_id')->constrained()->nullOnDelete();
                }

                if (! Schema::hasColumn('goods_received_notes', 'quantity_received')) {
                    $table->decimal('quantity_received', 14, 3)->default(0)->after('received_date');
                }

                if (! Schema::hasColumn('goods_received_notes', 'unit_cost')) {
                    $table->decimal('unit_cost', 14, 2)->default(0)->after('quantity_received');
                }

                if (! Schema::hasColumn('goods_received_notes', 'line_total')) {
                    $table->decimal('line_total', 14, 2)->default(0)->after('unit_cost');
                }
            });
        }

        if (! Schema::hasTable('stock_movements')) {
            Schema::create('stock_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('type');
                $table->decimal('quantity', 14, 3);
                $table->decimal('balance_after', 14, 3)->default(0);
                $table->string('source_type')->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->string('reference')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['source_type', 'source_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');

        if (Schema::hasTable('goods_received_notes')) {
            Schema::table('goods_received_notes', function (Blueprint $table) {
                foreach (['line_total', 'unit_cost', 'quantity_received'] as $column) {
                    if (Schema::hasColumn('goods_received_notes', $column)) {
                        $table->dropColumn($column);
                    }
                }

                if (Schema::hasColumn('goods_received_notes', 'product_id')) {
                    $table->dropConstrainedForeignId('product_id');
                }
            });
        }

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'reorder_level')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('reorder_level');
            });
        }
    }
};
