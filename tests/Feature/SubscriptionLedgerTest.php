<?php

namespace Tests\Feature;

use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_admin_cannot_read_platform_financial_records(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('platform.subscription-payments'))->assertForbidden();
    }

    public function test_ledger_filters_records_and_keeps_currencies_and_failed_payments_separate(): void
    {
        $tenant = Tenant::create(['name' => 'Ledger Client', 'slug' => 'ledger-client', 'status' => 'active']);
        $invoice = SubscriptionInvoice::create([
            'tenant_id' => $tenant->id, 'invoice_number' => 'LEDGER-001',
            'customer_name' => 'Ledger Client', 'status' => 'paid',
            'currency' => 'KES', 'subtotal' => 5000, 'total' => 5000,
        ]);
        foreach ([['successful', 'KES', 5000], ['paid', 'USD', 40], ['failed', 'KES', 5000]] as [$status, $currency, $amount]) {
            SubscriptionPayment::create([
                'tenant_id' => $tenant->id, 'subscription_invoice_id' => $invoice->id,
                'provider' => 'manual', 'status' => $status, 'currency' => $currency,
                'amount' => $amount, 'paid_at' => $status === 'failed' ? null : now(),
            ]);
        }
        $owner = User::factory()->create(['role' => 'super_admin', 'is_active' => true, 'status' => 'Active']);
        $this->actingAs($owner)->get(route('platform.subscription-payments'))
            ->assertOk()->assertSee('LEDGER-001')
            ->assertViewHas('totals', fn ($totals) => $totals->count() === 2
                && (float) $totals->firstWhere('currency', 'KES')->total === 5000.0
                && (float) $totals->firstWhere('currency', 'USD')->total === 40.0);
        $this->get(route('platform.subscription-payments', ['status' => 'failed', 'q' => 'LEDGER-001']))
            ->assertOk()->assertViewHas('payments', fn ($payments) => $payments->total() === 1)
            ->assertViewHas('totals', fn ($totals) => $totals->isEmpty());
        $this->get(route('platform.subscription-payments', ['to' => now()->subDay()->toDateString()]))
            ->assertOk()->assertViewHas('payments', fn ($payments) => $payments->total() === 0);
    }
}
