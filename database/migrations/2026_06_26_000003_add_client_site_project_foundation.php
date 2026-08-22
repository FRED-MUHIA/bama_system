<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'type')) {
                $table->string('type')->default('individual')->after('id');
            }
            if (! Schema::hasColumn('clients', 'billing_address')) {
                $table->text('billing_address')->nullable()->after('address');
            }
            if (! Schema::hasColumn('clients', 'kra_pin')) {
                $table->string('kra_pin')->nullable()->after('billing_address');
            }
        });

        if (! Schema::hasTable('contacts')) {
            Schema::create('contacts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('client_id')->constrained()->cascadeOnDelete();
                $table->string('full_name');
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('position')->nullable();
                $table->boolean('is_primary')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('sites')) {
            Schema::create('sites', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('client_id')->constrained()->cascadeOnDelete();
                $table->string('site_name');
                $table->text('address')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('projects')) {
            Schema::create('projects', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('client_id')->constrained()->cascadeOnDelete();
                $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
                $table->string('project_name');
                $table->string('status')->default('Lead');
                $table->text('scope')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('quotations', function (Blueprint $table) {
            if (! Schema::hasColumn('quotations', 'site_id')) {
                $table->foreignId('site_id')->nullable()->after('client_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('quotations', 'project_id')) {
                $table->foreignId('project_id')->nullable()->after('site_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('quotations', 'contact_id')) {
                $table->foreignId('contact_id')->nullable()->after('project_id')->constrained()->nullOnDelete();
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'site_id')) {
                $table->foreignId('site_id')->nullable()->after('client_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('invoices', 'project_id')) {
                $table->foreignId('project_id')->nullable()->after('site_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('invoices', 'contact_id')) {
                $table->foreignId('contact_id')->nullable()->after('project_id')->constrained()->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            foreach (['contact_id', 'project_id', 'site_id'] as $column) {
                if (Schema::hasColumn('invoices', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }
        });

        Schema::table('quotations', function (Blueprint $table) {
            foreach (['contact_id', 'project_id', 'site_id'] as $column) {
                if (Schema::hasColumn('quotations', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }
        });

        Schema::dropIfExists('projects');
        Schema::dropIfExists('sites');
        Schema::dropIfExists('contacts');

        Schema::table('clients', function (Blueprint $table) {
            foreach (['kra_pin', 'billing_address', 'type'] as $column) {
                if (Schema::hasColumn('clients', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
