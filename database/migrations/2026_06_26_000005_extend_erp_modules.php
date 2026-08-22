<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['enable_password_login', 'enable_otp_login', 'enable_magic_link_login'] as $column) {
                if (! Schema::hasColumn('users', $column)) {
                    $table->boolean($column)->default($column !== 'enable_magic_link_login')->after('is_active');
                }
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'invoice_type')) {
                $table->string('invoice_type')->default('STANDARD')->after('parent_invoice_id');
            }
        });

        Schema::table('receipts', function (Blueprint $table) {
            if (! Schema::hasColumn('receipts', 'project_id')) {
                $table->foreignId('project_id')->nullable()->after('invoice_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('receipts', 'status')) {
                $table->string('status')->default('Paid')->after('balance_remaining');
            }
        });

        $this->createIfMissing('project_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->string('description');
            $table->decimal('expected_amount', 12, 2)->default(0);
            $table->decimal('actual_amount', 12, 2)->default(0);
            $table->timestamps();
        });

        $this->createIfMissing('project_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('expense_date');
            $table->string('category');
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();
        });

        $this->createIfMissing('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('kra_pin')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('supplier_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('quote_number')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status')->default('Draft');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('po_number');
            $table->date('order_date')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status')->default('Draft');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('goods_received_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->date('received_date');
            $table->string('received_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('supplier_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number');
            $table->date('invoice_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->string('status')->default('Draft');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('payment_date');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('warranties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('manufacturer')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('starts_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->string('status')->default('Active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('warranty_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('warranty_id')->constrained()->cascadeOnDelete();
            $table->date('claim_date');
            $table->string('status')->default('Claim Open');
            $table->text('issue')->nullable();
            $table->text('resolution')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('client_portal_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->string('token', 80)->unique();
            $table->string('status')->default('Invited');
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('email')->nullable();
            $table->string('code');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('login_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('email')->nullable();
            $table->string('token', 80)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('document_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->longText('content');
            $table->string('output_format')->default('PDF');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $this->createIfMissing('project_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('document_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('document_type');
            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('status')->default('Draft');
            $table->timestamps();
        });

        $this->createIfMissing('handover_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('handover_date')->nullable();
            $table->string('status')->default('Draft');
            $table->string('signature_name')->nullable();
            $table->longText('signature_data')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('handover_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('handover_record_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->boolean('is_done')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('receipt_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_allocation_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'receipt_allocations',
            'handover_checklist_items',
            'handover_records',
            'project_documents',
            'document_templates',
            'login_tokens',
            'otp_codes',
            'client_portal_invitations',
            'warranty_claims',
            'warranties',
            'supplier_payments',
            'supplier_invoices',
            'goods_received_notes',
            'purchase_orders',
            'supplier_quotes',
            'suppliers',
            'project_expenses',
            'project_costs',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function createIfMissing(string $table, callable $callback): void
    {
        if (! Schema::hasTable($table)) {
            Schema::create($table, $callback);
        }
    }
};
