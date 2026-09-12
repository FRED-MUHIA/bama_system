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
        $this->createOperationalTables();
        $this->registerModule();
    }

    public function down(): void
    {
        foreach ([
            'chama_audit_events',
            'chama_member_exits',
            'chama_cash_handovers',
            'chama_payment_allocation_lines',
            'chama_payment_allocations',
            'chama_unallocated_payments',
            'chama_documents',
            'chama_votes',
            'chama_ballots',
            'chama_leadership',
            'chama_minutes',
            'chama_agenda_items',
            'chama_attendance',
            'chama_meetings',
            'chama_dividend_allocations',
            'chama_dividend_runs',
            'chama_investment_income',
            'chama_investments',
            'chama_share_transactions',
            'chama_welfare_requests',
            'chama_fines',
            'chama_loan_repayments',
            'chama_loan_schedules',
            'chama_loan_guarantors',
            'chama_loans',
            'chama_loan_products',
            'chama_table_banking_sessions',
            'chama_merry_go_round_rounds',
            'chama_merry_go_round_members',
            'chama_merry_go_round_cycles',
            'chama_savings_transactions',
            'chama_savings_accounts',
            'chama_contributions',
            'chama_contribution_schedules',
            'chama_contribution_types',
            'chama_rules',
            'chama_members',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function createOperationalTables(): void
    {
        $this->createIfMissing('chama_members', function (Blueprint $table) {
            $this->scope($table);
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('member_number');
            $table->string('full_name');
            $table->string('national_id')->nullable()->index();
            $table->string('phone')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('address')->nullable();
            $table->json('next_of_kin')->nullable();
            $table->date('join_date')->nullable();
            $table->string('membership_type')->default('Standard')->index();
            $table->string('status')->default('Applicant')->index();
            $table->string('occupation')->nullable();
            $table->string('employer')->nullable();
            $table->string('profile_photo_path')->nullable();
            $table->string('signature_path')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('registration_fee_due', 14, 2)->default(0);
            $table->decimal('registration_fee_paid', 14, 2)->default(0);
            $table->decimal('share_capital_required', 14, 2)->default(0);
            $table->decimal('initial_contribution_required', 14, 2)->default(0);
            $table->timestamp('kyc_verified_at')->nullable();
            $table->timestamp('constitution_accepted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'member_number'], 'chama_members_tenant_number_unique');
            $table->index(['tenant_id', 'status'], 'chama_members_tenant_status_idx');
        });

        $this->createIfMissing('chama_rules', function (Blueprint $table) {
            $this->scope($table);
            $table->string('rule_number');
            $table->unsignedInteger('version')->default(1);
            $table->string('name')->default('Chama Rules');
            $table->json('rules');
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->string('status')->default('Draft')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'rule_number'], 'chama_rules_tenant_number_unique');
        });

        $this->createIfMissing('chama_contribution_types', function (Blueprint $table) {
            $this->scope($table);
            $table->string('type_number');
            $table->string('name');
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('frequency')->default('Monthly')->index();
            $table->boolean('is_mandatory')->default(true)->index();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedInteger('grace_period_days')->default(0);
            $table->decimal('late_penalty', 14, 2)->default(0);
            $table->json('applicable_member_ids')->nullable();
            $table->string('fund_bucket')->default('General')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'type_number'], 'chama_contribution_types_tenant_number_unique');
        });

        $this->createIfMissing('chama_contribution_schedules', function (Blueprint $table) {
            $this->scope($table);
            $table->foreignId('contribution_type_id')->constrained('chama_contribution_types')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('chama_members')->cascadeOnDelete();
            $table->string('schedule_number');
            $table->string('period');
            $table->date('due_date')->index();
            $table->decimal('amount_due', 14, 2)->default(0);
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->decimal('penalty', 14, 2)->default(0);
            $table->decimal('balance', 14, 2)->default(0);
            $table->string('status')->default('Pending')->index();
            $table->timestamp('waived_at')->nullable();
            $table->foreignId('waived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id', 'schedule_number'], 'chama_schedules_tenant_number_unique');
            $table->unique(['tenant_id', 'contribution_type_id', 'member_id', 'period'], 'chama_schedule_period_unique');
        });

        $this->createIfMissing('chama_contributions', function (Blueprint $table) {
            $this->scope($table);
            $table->foreignId('member_id')->constrained('chama_members')->cascadeOnDelete();
            $table->foreignId('contribution_type_id')->nullable()->constrained('chama_contribution_types')->nullOnDelete();
            $table->foreignId('contribution_schedule_id')->nullable()->constrained('chama_contribution_schedules')->nullOnDelete();
            $table->string('contribution_number');
            $table->string('period')->nullable()->index();
            $table->decimal('amount_due', 14, 2)->default(0);
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->date('payment_date')->nullable()->index();
            $table->string('payment_method')->default('Cash')->index();
            $table->string('payment_reference')->nullable()->index();
            $table->decimal('balance', 14, 2)->default(0);
            $table->string('status')->default('Pending')->index();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'contribution_number'], 'chama_contributions_tenant_number_unique');
        });

        $this->createIfMissing('chama_savings_accounts', function (Blueprint $table) {
            $this->scope($table);
            $table->foreignId('member_id')->constrained('chama_members')->cascadeOnDelete();
            $table->string('account_number');
            $table->string('account_type')->default('Regular Savings')->index();
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->decimal('current_balance', 14, 2)->default(0);
            $table->decimal('interest_rate', 8, 4)->default(0);
            $table->string('status')->default('Active')->index();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'account_number'], 'chama_savings_accounts_tenant_number_unique');
        });

        $this->createIfMissing('chama_savings_transactions', function (Blueprint $table) {
            $this->scope($table);
            $table->foreignId('savings_account_id')->constrained('chama_savings_accounts')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('chama_members')->cascadeOnDelete();
            $table->string('transaction_number');
            $table->string('transaction_type')->index();
            $table->decimal('amount', 14, 2);
            $table->decimal('balance_after', 14, 2)->default(0);
            $table->date('transaction_date')->index();
            $table->string('payment_method')->nullable()->index();
            $table->string('reference')->nullable()->index();
            $table->string('status')->default('Posted')->index();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'transaction_number'], 'chama_savings_transactions_tenant_number_unique');
        });

        $this->createIfMissing('chama_merry_go_round_cycles', function (Blueprint $table) {
            $this->scope($table);
            $table->string('cycle_number');
            $table->string('name');
            $table->decimal('contribution_amount', 14, 2)->default(0);
            $table->string('frequency')->default('Monthly')->index();
            $table->date('start_date')->nullable();
            $table->date('next_payout_date')->nullable()->index();
            $table->decimal('payout_amount', 14, 2)->default(0);
            $table->foreignId('current_beneficiary_id')->nullable()->constrained('chama_members')->nullOnDelete();
            $table->foreignId('next_beneficiary_id')->nullable()->constrained('chama_members')->nullOnDelete();
            $table->string('status')->default('Planned')->index();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'cycle_number'], 'chama_mgro_cycles_tenant_number_unique');
        });

        $this->createIfMissing('chama_merry_go_round_members', function (Blueprint $table) {
            $this->scope($table);
            $table->foreignId('cycle_id')->constrained('chama_merry_go_round_cycles')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('chama_members')->cascadeOnDelete();
            $table->unsignedInteger('payout_order')->default(1);
            $table->string('status')->default('Active')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'cycle_id', 'member_id'], 'chama_mgro_members_unique');
            $table->unique(['tenant_id', 'cycle_id', 'payout_order'], 'chama_mgro_order_unique');
        });

        $this->createIfMissing('chama_merry_go_round_rounds', function (Blueprint $table) {
            $this->scope($table);
            $table->foreignId('cycle_id')->constrained('chama_merry_go_round_cycles')->cascadeOnDelete();
            $table->foreignId('beneficiary_id')->constrained('chama_members')->cascadeOnDelete();
            $table->string('round_number');
            $table->unsignedInteger('round_index')->default(1);
            $table->date('due_date')->nullable()->index();
            $table->decimal('expected_amount', 14, 2)->default(0);
            $table->decimal('amount_collected', 14, 2)->default(0);
            $table->decimal('payout_amount', 14, 2)->default(0);
            $table->string('payout_method')->nullable();
            $table->string('transaction_reference')->nullable()->index();
            $table->date('payout_date')->nullable()->index();
            $table->string('status')->default('Pending')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'round_number'], 'chama_mgro_rounds_tenant_number_unique');
        });

        $this->createIfMissing('chama_table_banking_sessions', function (Blueprint $table) {
            $this->scope($table);
            $table->unsignedBigInteger('meeting_id')->nullable()->index();
            $table->string('session_number');
            $table->date('session_date')->index();
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->decimal('contributions', 14, 2)->default(0);
            $table->decimal('savings', 14, 2)->default(0);
            $table->decimal('loan_repayments', 14, 2)->default(0);
            $table->decimal('interest', 14, 2)->default(0);
            $table->decimal('fines', 14, 2)->default(0);
            $table->decimal('expenses', 14, 2)->default(0);
            $table->decimal('loans_disbursed', 14, 2)->default(0);
            $table->decimal('closing_balance', 14, 2)->default(0);
            $table->decimal('expected_closing_balance', 14, 2)->default(0);
            $table->decimal('variance', 14, 2)->default(0);
            $table->boolean('is_balanced')->default(false)->index();
            $table->string('status')->default('Draft')->index();
            $table->json('notes')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'session_number'], 'chama_table_sessions_tenant_number_unique');
        });

        $this->createIfMissing('chama_loan_products', function (Blueprint $table) {
            $this->scope($table);
            $table->string('product_number');
            $table->string('name');
            $table->decimal('interest_rate', 8, 4)->default(0);
            $table->string('interest_method')->default('Flat Rate')->index();
            $table->unsignedInteger('term_months')->default(1);
            $table->decimal('minimum_amount', 14, 2)->default(0);
            $table->decimal('maximum_amount', 14, 2)->default(0);
            $table->unsignedInteger('guarantor_count')->default(0);
            $table->string('loan_limit_formula')->default('Fixed Maximum');
            $table->json('eligibility_rules')->nullable();
            $table->json('repayment_allocation_order')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'product_number'], 'chama_loan_products_tenant_number_unique');
        });

        $this->createIfMissing('chama_loans', function (Blueprint $table) {
            $this->scope($table);
            $table->foreignId('loan_product_id')->nullable()->constrained('chama_loan_products')->nullOnDelete();
            $table->foreignId('member_id')->constrained('chama_members')->cascadeOnDelete();
            $table->string('loan_number');
            $table->decimal('principal', 14, 2)->default(0);
            $table->decimal('interest_rate', 8, 4)->default(0);
            $table->string('interest_method')->default('Flat Rate')->index();
            $table->unsignedInteger('term_months')->default(1);
            $table->date('application_date')->nullable()->index();
            $table->date('disbursement_date')->nullable()->index();
            $table->text('purpose')->nullable();
            $table->string('status')->default('Draft')->index();
            $table->json('eligibility_result')->nullable();
            $table->decimal('approved_amount', 14, 2)->default(0);
            $table->decimal('outstanding_principal', 14, 2)->default(0);
            $table->decimal('outstanding_interest', 14, 2)->default(0);
            $table->decimal('outstanding_penalties', 14, 2)->default(0);
            $table->decimal('total_paid', 14, 2)->default(0);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('disbursed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'loan_number'], 'chama_loans_tenant_number_unique');
        });

        $this->createIfMissing('chama_loan_guarantors', function (Blueprint $table) {
            $this->scope($table);
            $table->foreignId('loan_id')->constrained('chama_loans')->cascadeOnDelete();
            $table->foreignId('guarantor_member_id')->constrained('chama_members')->cascadeOnDelete();
            $table->decimal('guaranteed_amount', 14, 2)->default(0);
            $table->decimal('exposure_amount', 14, 2)->default(0);
            $table->string('status')->default('Pending')->index();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'loan_id', 'guarantor_member_id'], 'chama_loan_guarantors_unique');
        });

        $this->createIfMissing('chama_loan_schedules', function (Blueprint $table) {
            $this->scope($table);
            $table->foreignId('loan_id')->constrained('chama_loans')->cascadeOnDelete();
            $table->unsignedInteger('installment_number');
            $table->date('due_date')->index();
            $table->decimal('principal', 14, 2)->default(0);
            $table->decimal('interest', 14, 2)->default(0);
            $table->decimal('penalty', 14, 2)->default(0);
            $table->decimal('total_due', 14, 2)->default(0);
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->decimal('balance', 14, 2)->default(0);
            $table->string('status')->default('Upcoming')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'loan_id', 'installment_number'], 'chama_loan_schedule_unique');
        });

        $this->createIfMissing('chama_loan_repayments', function (Blueprint $table) {
            $this->scope($table);
            $table->foreignId('loan_id')->constrained('chama_loans')->cascadeOnDelete();
            $table->foreignId('loan_schedule_id')->nullable()->constrained('chama_loan_schedules')->nullOnDelete();
            $table->foreignId('member_id')->constrained('chama_members')->cascadeOnDelete();
            $table->string('repayment_number');
            $table->date('payment_date')->index();
            $table->decimal('amount', 14, 2)->default(0);
            $table->decimal('penalty_amount', 14, 2)->default(0);
            $table->decimal('interest_amount', 14, 2)->default(0);
            $table->decimal('principal_amount', 14, 2)->default(0);
            $table->string('payment_method')->default('Cash')->index();
            $table->string('reference')->nullable()->index();
            $table->string('status')->default('Posted')->index();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->json('allocation')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'repayment_number'], 'chama_loan_repayments_tenant_number_unique');
        });

        $this->createIfMissing('chama_fines', function (Blueprint $table) {
            $this->scope($table);
            $table->foreignId('member_id')->constrained('chama_members')->cascadeOnDelete();
            $table->string('fine_number');
            $table->string('fine_type')->index();
            $table->decimal('amount', 14, 2)->default(0);
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->text('reason')->nullable();
            $table->date('fine_date')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('Pending')->index();
            $table->timestamp('waived_at')->nullable();
            $table->foreignId('waived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id', 'fine_number'], 'chama_fines_tenant_number_unique');
        });

        $this->createIfMissing('chama_welfare_requests', function (Blueprint $table) {
            $this->scope($table);
            $table->foreignId('member_id')->constrained('chama_members')->cascadeOnDelete();
            $table->string('request_number');
            $table->string('request_type')->index();
            $table->decimal('amount_requested', 14, 2)->default(0);
            $table->decimal('amount_approved', 14, 2)->default(0);
            $table->text('reason')->nullable();
            $table->json('supporting_documents')->nullable();
            $table->string('status')->default('Pending')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->date('payment_date')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'request_number'], 'chama_welfare_tenant_number_unique');
        });

        $this->createIfMissing('chama_share_transactions', function (Blueprint $table) {
            $this->scope($table);
            $table->foreignId('member_id')->constrained('chama_members')->cascadeOnDelete();
            $table->string('transaction_number');
            $table->string('transaction_type')->default('Purchase')->index();
            $table->decimal('share_units', 14, 4)->default(0);
            $table->decimal('price_per_share', 14, 4)->default(0);
            $table->decimal('share_value', 14, 2)->default(0);
            $table->date('transaction_date')->index();
            $table->string('status')->default('Posted')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'transaction_number'], 'chama_share_transactions_tenant_number_unique');
        });

        $this->createIfMissing('chama_investments', function (Blueprint $table) {
            $this->scope($table);
            $table->string('investment_number');
            $table->string('name');
            $table->string('investment_type')->index();
            $table->date('purchase_date')->nullable()->index();
            $table->decimal('initial_cost', 16, 2)->default(0);
            $table->decimal('current_value', 16, 2)->default(0);
            $table->decimal('income', 16, 2)->default(0);
            $table->decimal('expenses', 16, 2)->default(0);
            $table->string('allocation_basis')->default('Equal Shares')->index();
            $table->json('ownership_allocations')->nullable();
            $table->json('documents')->nullable();
            $table->string('status')->default('Active')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'investment_number'], 'chama_investments_tenant_number_unique');
        });

        $this->createIfMissing('chama_investment_income', function (Blueprint $table) {
            $this->scope($table);
            $table->foreignId('investment_id')->constrained('chama_investments')->cascadeOnDelete();
            $table->string('income_number');
            $table->string('income_type')->index();
            $table->decimal('amount', 14, 2)->default(0);
            $table->date('income_date')->index();
            $table->string('allocation_status')->default('Retained')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'income_number'], 'chama_investment_income_tenant_number_unique');
        });

        $this->createIfMissing('chama_dividend_runs', function (Blueprint $table) {
            $this->scope($table);
            $table->string('dividend_number');
            $table->string('period');
            $table->string('distribution_basis')->default('Share Capital')->index();
            $table->decimal('profit_available', 16, 2)->default(0);
            $table->decimal('reserve_allocation', 16, 2)->default(0);
            $table->decimal('amount_distributed', 16, 2)->default(0);
            $table->string('status')->default('Draft')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'dividend_number'], 'chama_dividend_runs_tenant_number_unique');
        });

        $this->createIfMissing('chama_dividend_allocations', function (Blueprint $table) {
            $this->scope($table);
            $table->foreignId('dividend_run_id')->constrained('chama_dividend_runs')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('chama_members')->cascadeOnDelete();
            $table->decimal('member_basis', 16, 4)->default(0);
            $table->decimal('percentage', 8, 4)->default(0);
            $table->decimal('gross_dividend', 14, 2)->default(0);
            $table->decimal('deductions', 14, 2)->default(0);
            $table->decimal('net_dividend', 14, 2)->default(0);
            $table->string('payment_status')->default('Pending')->index();
            $table->date('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'dividend_run_id', 'member_id'], 'chama_dividend_allocations_unique');
        });

        $this->createIfMissing('chama_meetings', function (Blueprint $table) {
            $this->scope($table);
            $table->string('meeting_number');
            $table->string('meeting_type')->default('Regular Meeting')->index();
            $table->string('title');
            $table->date('meeting_date')->index();
            $table->time('meeting_time')->nullable();
            $table->string('venue')->nullable();
            $table->string('online_link')->nullable();
            $table->text('agenda')->nullable();
            $table->foreignId('chairperson_id')->nullable()->constrained('chama_members')->nullOnDelete();
            $table->foreignId('secretary_id')->nullable()->constrained('chama_members')->nullOnDelete();
            $table->unsignedInteger('quorum_required_count')->nullable();
            $table->decimal('quorum_required_percent', 5, 2)->nullable();
            $table->unsignedInteger('members_present_count')->default(0);
            $table->boolean('quorum_achieved')->default(false)->index();
            $table->string('status')->default('Scheduled')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'meeting_number'], 'chama_meetings_tenant_number_unique');
        });

        $this->createIfMissing('chama_attendance', function (Blueprint $table) {
            $this->scope($table);
            $table->foreignId('meeting_id')->constrained('chama_meetings')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('chama_members')->cascadeOnDelete();
            $table->string('status')->default('Present')->index();
            $table->timestamp('checked_in_at')->nullable();
            $table->string('qr_reference')->nullable();
            $table->decimal('fine_amount', 14, 2)->default(0);
            $table->timestamps();
            $table->unique(['tenant_id', 'meeting_id', 'member_id'], 'chama_attendance_unique');
        });

        $this->createIfMissing('chama_agenda_items', function (Blueprint $table) {
            $this->scope($table);
            $table->foreignId('meeting_id')->constrained('chama_meetings')->cascadeOnDelete();
            $table->unsignedInteger('item_number')->default(1);
            $table->string('topic');
            $table->foreignId('presenter_id')->nullable()->constrained('chama_members')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->text('decision')->nullable();
            $table->text('action_item')->nullable();
            $table->foreignId('responsible_member_id')->nullable()->constrained('chama_members')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->string('status')->default('Open')->index();
            $table->timestamps();
        });

        $this->createIfMissing('chama_minutes', function (Blueprint $table) {
            $this->scope($table);
            $table->foreignId('meeting_id')->constrained('chama_meetings')->cascadeOnDelete();
            $table->longText('content')->nullable();
            $table->json('financial_summary')->nullable();
            $table->json('loan_approvals')->nullable();
            $table->json('investment_decisions')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('status')->default('Draft')->index();
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('chama_leadership', function (Blueprint $table) {
            $this->scope($table);
            $table->foreignId('member_id')->constrained('chama_members')->cascadeOnDelete();
            $table->string('position')->index();
            $table->date('start_date')->nullable()->index();
            $table->date('end_date')->nullable()->index();
            $table->string('status')->default('Active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('chama_ballots', function (Blueprint $table) {
            $this->scope($table);
            $table->foreignId('meeting_id')->nullable()->constrained('chama_meetings')->nullOnDelete();
            $table->string('ballot_number');
            $table->string('title');
            $table->string('vote_type')->default('Yes / No')->index();
            $table->boolean('is_secret')->default(false)->index();
            $table->json('options')->nullable();
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->string('status')->default('Draft')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'ballot_number'], 'chama_ballots_tenant_number_unique');
        });

        $this->createIfMissing('chama_votes', function (Blueprint $table) {
            $this->scope($table);
            $table->foreignId('ballot_id')->constrained('chama_ballots')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('chama_members')->cascadeOnDelete();
            $table->foreignId('candidate_member_id')->nullable()->constrained('chama_members')->nullOnDelete();
            $table->string('vote_value')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('voted_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'ballot_id', 'member_id'], 'chama_votes_once_unique');
        });

        $this->createIfMissing('chama_documents', function (Blueprint $table) {
            $this->scope($table);
            $table->foreignId('member_id')->nullable()->constrained('chama_members')->nullOnDelete();
            $table->foreignId('document_template_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('documentable');
            $table->string('document_number');
            $table->string('document_type')->index();
            $table->string('title');
            $table->string('file_path')->nullable();
            $table->string('status')->default('Active')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'document_number'], 'chama_documents_tenant_number_unique');
        });

        $this->createIfMissing('chama_unallocated_payments', function (Blueprint $table) {
            $this->scope($table);
            $table->string('provider')->default('mpesa')->index();
            $table->string('transaction_reference')->index();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('phone')->nullable()->index();
            $table->string('account_reference')->nullable()->index();
            $table->json('payload')->nullable();
            $table->string('status')->default('Unallocated')->index();
            $table->foreignId('allocated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('allocated_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'provider', 'transaction_reference'], 'chama_unallocated_payment_unique');
        });

        $this->createIfMissing('chama_payment_allocations', function (Blueprint $table) {
            $this->scope($table);
            $table->foreignId('member_id')->nullable()->constrained('chama_members')->nullOnDelete();
            $table->string('allocation_number');
            $table->string('payment_method')->default('Cash')->index();
            $table->string('payment_reference')->nullable()->index();
            $table->decimal('amount', 14, 2)->default(0);
            $table->date('payment_date')->nullable()->index();
            $table->string('status')->default('Draft')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'allocation_number'], 'chama_payment_allocations_tenant_number_unique');
        });

        $this->createIfMissing('chama_payment_allocation_lines', function (Blueprint $table) {
            $this->scope($table);
            $table->foreignId('payment_allocation_id')->constrained('chama_payment_allocations')->cascadeOnDelete();
            $table->string('obligation_type')->index();
            $table->nullableMorphs('allocatable');
            $table->decimal('amount', 14, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('chama_cash_handovers', function (Blueprint $table) {
            $this->scope($table);
            $table->foreignId('meeting_id')->nullable()->constrained('chama_meetings')->nullOnDelete();
            $table->string('handover_number');
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 14, 2)->default(0);
            $table->foreignId('handed_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handed_over_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->string('status')->default('Pending')->index();
            $table->text('acknowledgement')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'handover_number'], 'chama_cash_handovers_tenant_number_unique');
        });

        $this->createIfMissing('chama_member_exits', function (Blueprint $table) {
            $this->scope($table);
            $table->foreignId('member_id')->constrained('chama_members')->cascadeOnDelete();
            $table->string('exit_number');
            $table->string('reason')->index();
            $table->date('reported_date')->nullable();
            $table->date('effective_date')->nullable();
            $table->json('next_of_kin')->nullable();
            $table->json('supporting_documents')->nullable();
            $table->decimal('savings_balance', 14, 2)->default(0);
            $table->decimal('share_capital', 14, 2)->default(0);
            $table->decimal('outstanding_loan', 14, 2)->default(0);
            $table->decimal('guaranteed_loans', 14, 2)->default(0);
            $table->decimal('fines', 14, 2)->default(0);
            $table->decimal('pending_contributions', 14, 2)->default(0);
            $table->decimal('dividend_entitlement', 14, 2)->default(0);
            $table->decimal('exit_fees', 14, 2)->default(0);
            $table->decimal('net_settlement', 14, 2)->default(0);
            $table->string('status')->default('Draft')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'exit_number'], 'chama_member_exits_tenant_number_unique');
        });

        $this->createIfMissing('chama_audit_events', function (Blueprint $table) {
            $this->scope($table);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event')->index();
            $table->nullableMorphs('auditable');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('reference')->nullable()->index();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    private function createIfMissing(string $table, callable $definition): void
    {
        if (! Schema::hasTable($table)) {
            Schema::create($table, $definition);
        }
    }

    private function scope(Blueprint $table): void
    {
        $table->id();
        $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
        $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();

        if (Schema::hasTable('branches')) {
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
        } else {
            $table->unsignedBigInteger('branch_id')->nullable()->index();
        }

        $table->index(['tenant_id', 'business_id'], 'chama_scope_tenant_business_idx_'.Str::random(4));
    }

    private function registerModule(): void
    {
        if (! Schema::hasTable('modules')) {
            return;
        }

        $definition = require base_path('Modules/Chama/module.php');

        $packageId = $this->upsertModule([
            'slug' => 'chama',
            'name' => $definition['name'],
            'namespace' => 'Modules\\Chama',
            'type' => 'industry',
            'industry' => 'chama',
            'icon' => 'bi-people-fill',
            'route' => 'chama.dashboard',
            'permissions' => $definition['permissions'] ?? ['chama.view'],
            'menu' => ['label' => 'Chama Management', 'route' => 'chama.dashboard'],
            'widgets' => $definition['widgets'] ?? ['chama-overview'],
            'is_core' => false,
            'is_active' => true,
        ]);

        $this->attachIndustryModule('chama', $packageId);

        foreach ($definition['modules'] ?? [] as $moduleName) {
            $moduleSlug = Str::slug('chama-'.$moduleName);

            $moduleId = $this->upsertModule([
                'slug' => $moduleSlug,
                'name' => $moduleName,
                'namespace' => 'Modules\\Chama',
                'type' => 'industry',
                'industry' => 'chama',
                'icon' => 'bi-people-fill',
                'route' => 'chama.dashboard',
                'permissions' => [$moduleSlug.'.view', $moduleSlug.'.manage'],
                'menu' => ['label' => $moduleName, 'route' => 'chama.dashboard'],
                'widgets' => [$moduleSlug.'-summary'],
                'is_core' => false,
                'is_active' => true,
            ]);

            $this->attachIndustryModule('chama', $moduleId);
            $this->upsertWidget($moduleSlug.'-summary', $moduleName.' Summary', $moduleSlug, 'chama', $moduleSlug.'.view');
        }

        foreach ($definition['widgets'] ?? [] as $widget) {
            $this->upsertWidget($widget, Str::headline($widget), 'chama', 'chama', 'chama.dashboard');
        }

        $this->enableForExistingChamaTenants($packageId);
    }

    private function upsertModule(array $module): int
    {
        DB::table('modules')->updateOrInsert(
            ['slug' => $module['slug']],
            [
                'name' => $module['name'],
                'namespace' => $module['namespace'],
                'type' => $module['type'],
                'industry' => $module['industry'],
                'icon' => $module['icon'],
                'route' => $module['route'],
                'permissions' => json_encode($module['permissions']),
                'menu' => json_encode($module['menu']),
                'widgets' => json_encode($module['widgets']),
                'is_core' => $module['is_core'],
                'is_active' => $module['is_active'],
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return (int) DB::table('modules')->where('slug', $module['slug'])->value('id');
    }

    private function attachIndustryModule(string $industry, int $moduleId): void
    {
        if (! Schema::hasTable('industry_modules') || ! $moduleId) {
            return;
        }

        DB::table('industry_modules')->updateOrInsert(
            ['industry' => $industry, 'module_id' => $moduleId],
            ['enabled_by_default' => true, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    private function upsertWidget(string $slug, string $name, ?string $moduleSlug, ?string $industry, ?string $permission): void
    {
        if (! Schema::hasTable('dashboard_widgets')) {
            return;
        }

        DB::table('dashboard_widgets')->updateOrInsert(
            ['slug' => $slug],
            [
                'name' => $name,
                'module_slug' => $moduleSlug,
                'industry' => $industry,
                'component' => 'dashboard.widgets.metric-card',
                'permission' => $permission,
                'settings_schema' => json_encode([
                    'supports_tenant_filters' => true,
                    'supports_period_filters' => true,
                    'supports_member_filters' => true,
                ]),
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function enableForExistingChamaTenants(int $packageId): void
    {
        if (! Schema::hasTable('tenants') || ! Schema::hasTable('tenant_modules') || ! $packageId) {
            return;
        }

        DB::table('tenants')
            ->where('industry', 'chama')
            ->pluck('id')
            ->each(function ($tenantId) use ($packageId) {
                DB::table('tenant_modules')->updateOrInsert(
                    ['tenant_id' => $tenantId, 'module_id' => $packageId],
                    ['enabled' => true, 'enabled_at' => now(), 'updated_at' => now(), 'created_at' => now()]
                );
            });
    }
};
