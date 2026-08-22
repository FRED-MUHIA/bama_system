<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('admin')->after('email');
            }

            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('role');
            }
        });

        $this->createIfMissing('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->default('BAMA');
            $table->string('logo_path')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('website')->nullable();
            $table->string('tax_name')->default('VAT');
            $table->decimal('tax_rate', 8, 2)->default(0);
            $table->text('default_terms')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('company_name')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('custom');
            $table->text('details')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $this->createIfMissing('terms_conditions', function (Blueprint $table) {
            $table->id();
            $table->string('title')->default('Default Terms');
            $table->text('content');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        $this->createIfMissing('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('quotation_number')->unique();
            $table->date('quotation_date');
            $table->date('valid_until')->nullable();
            $table->string('status')->default('draft');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('terms')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->text('description');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax_rate', 8, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->timestamps();
        });

        $this->createIfMissing('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('payment_status')->default('unpaid');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('balance', 12, 2)->default(0);
            $table->text('terms')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->text('description');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax_rate', 8, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->timestamps();
        });

        $this->createIfMissing('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('receipt_number')->unique();
            $table->decimal('amount_paid', 12, 2);
            $table->decimal('balance_remaining', 12, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->date('payment_date');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('email_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('emailable');
            $table->string('recipient_email');
            $table->string('subject');
            $table->text('message')->nullable();
            $table->string('status')->default('sent');
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('terms_conditions');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('company_settings');

        Schema::table('users', function (Blueprint $table) {
            $columns = array_values(array_filter(['role', 'is_active'], fn (string $column) => Schema::hasColumn('users', $column)));

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }

    private function createIfMissing(string $table, callable $definition): void
    {
        if (! Schema::hasTable($table)) {
            Schema::create($table, $definition);
        }
    }
};
