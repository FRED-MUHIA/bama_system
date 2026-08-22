<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('real_estate_tenants', function (Blueprint $table) {
            $table->string('offboarding_step')->nullable()->after('status');
            $table->date('notice_date')->nullable()->after('offboarding_step');
            $table->date('termination_date')->nullable()->after('notice_date');
            $table->date('final_inspection_date')->nullable()->after('termination_date');
            $table->timestamp('utility_reconciled_at')->nullable()->after('final_inspection_date');
            $table->timestamp('final_billed_at')->nullable()->after('utility_reconciled_at');
            $table->timestamp('deposit_settled_at')->nullable()->after('final_billed_at');
            $table->date('move_out_date')->nullable()->after('deposit_settled_at');
            $table->timestamp('archived_at')->nullable()->after('move_out_date');
            $table->timestamp('restored_at')->nullable()->after('archived_at');
            $table->foreignId('archived_by')->nullable()->after('restored_at')->constrained('users')->nullOnDelete();
            $table->text('offboarding_notes')->nullable()->after('archived_by');
            $table->index(['business_id', 'status'], 're_tenants_business_status_idx');
            $table->index('archived_at', 're_tenants_archived_at_idx');
        });

        DB::table('real_estate_tenants')->where('status', 'Former Tenant')->update(['status' => 'Moved Out']);
    }

    public function down(): void
    {
        Schema::table('real_estate_tenants', function (Blueprint $table) {
            $table->dropIndex('re_tenants_business_status_idx');
            $table->dropIndex('re_tenants_archived_at_idx');
            $table->dropConstrainedForeignId('archived_by');
            $table->dropColumn([
                'offboarding_step',
                'notice_date',
                'termination_date',
                'final_inspection_date',
                'utility_reconciled_at',
                'final_billed_at',
                'deposit_settled_at',
                'move_out_date',
                'archived_at',
                'restored_at',
                'offboarding_notes',
            ]);
        });
    }
};
