<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\CompanySetting;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Billing\SubscriptionBillingService;
use App\Services\SubscriptionManager;
use App\Support\ActiveTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SubscriptionBillingEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_invoices_are_sent_to_the_business_profile_email(): void
    {
        Mail::fake();

        [$tenant, $plan, $subscription] = $this->subscriptionFixture();
        $billing = app(SubscriptionBillingService::class);

        $invoice = $billing->createInvoice($subscription, $plan);
        $sent = $billing->sendInvoice($invoice);

        $this->assertSame('billing@bama.test', $invoice->fresh()->billing_email);
        $this->assertSame(['billing@bama.test'], $billing->billingEmails($tenant));
        $this->assertSame(1, $sent);
        $this->assertDatabaseHas('email_logs', [
            'emailable_type' => (new SubscriptionInvoice)->getMorphClass(),
            'emailable_id' => $invoice->id,
            'recipient_email' => 'billing@bama.test',
            'status' => 'sent',
        ]);
        $this->assertDatabaseMissing('email_logs', [
            'recipient_email' => 'owner@bama.test',
        ]);
    }

    public function test_paid_subscription_payments_email_the_business_profile_email(): void
    {
        Mail::fake();

        [, $plan, $subscription] = $this->subscriptionFixture();
        $billing = app(SubscriptionBillingService::class);
        $invoice = $billing->createInvoice($subscription, $plan);
        $payment = SubscriptionPayment::create([
            'subscription_invoice_id' => $invoice->id,
            'tenant_id' => $invoice->tenant_id,
            'provider' => 'manual',
            'status' => 'initiated',
            'amount' => $invoice->total,
            'currency' => $invoice->currency,
        ]);

        $paidInvoice = $billing->markPaid($payment);

        $this->assertSame('paid', $paidInvoice->status);
        $this->assertDatabaseHas('email_logs', [
            'emailable_type' => $paidInvoice->getMorphClass(),
            'emailable_id' => $paidInvoice->id,
            'recipient_email' => 'billing@bama.test',
            'status' => 'sent',
        ]);
        $this->assertStringContainsString(
            'payment received',
            $paidInvoice->emailLogs()->latest()->firstOrFail()->subject
        );
    }

    public function test_future_renewal_repairs_a_stale_automatic_billing_lock(): void
    {
        [$tenant, , $subscription] = $this->subscriptionFixture();
        $tenant->forceFill(['status' => 'suspended'])->save();
        $subscription->forceFill([
            'status' => 'paused',
            'renews_at' => now()->addDays(3),
            'grace_ends_at' => now()->subDay(),
            'ends_at' => now()->subDays(2),
            'locked_at' => now()->subDay(),
        ])->save();
        ActiveTenant::switchTo($tenant);

        $state = app(SubscriptionManager::class)->billingState($tenant->fresh(['subscription']));

        $this->assertSame('renewal_due', $state['state']);
        $this->assertSame('active', $tenant->fresh()->status);
        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => 'active',
            'grace_ends_at' => null,
            'ends_at' => null,
            'locked_at' => null,
        ]);
    }

    public function test_billing_sweep_does_not_lock_a_future_renewal_with_an_old_grace_date(): void
    {
        Mail::fake();
        [$tenant, , $subscription] = $this->subscriptionFixture();
        $subscription->forceFill([
            'renews_at' => now()->addDays(10),
            'grace_ends_at' => now()->subDay(),
        ])->save();

        $stats = app(SubscriptionBillingService::class)->sweep();

        $this->assertSame(0, $stats['locked']);
        $this->assertSame('active', $tenant->fresh()->status);
        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertNull($subscription->fresh()->locked_at);
    }

    private function subscriptionFixture(): array
    {
        $tenant = Tenant::create([
            'name' => 'BAMA Test',
            'slug' => 'bama-test-'.str()->random(8),
            'industry' => 'printing',
            'status' => 'active',
        ]);

        $business = Business::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'BAMA Prints Test',
            'slug' => 'bama-prints-test-'.str()->random(8),
            'industry' => 'printing',
            'is_active' => true,
        ]);

        CompanySetting::withoutGlobalScopes()->create([
            'business_id' => $business->id,
            'company_name' => 'BAMA Prints Test',
            'email' => 'billing@bama.test',
            'tax_name' => 'VAT',
            'tax_rate' => 0,
        ]);

        $owner = User::factory()->create([
            'email' => 'owner@bama.test',
            'role' => 'admin',
            'is_active' => true,
            'status' => 'Active',
            'current_tenant_id' => $tenant->id,
        ]);

        $tenant->users()->attach($owner->id, [
            'role' => 'owner',
            'status' => 'active',
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $plan = Plan::create([
            'slug' => 'bama-growth-test-'.str()->random(8),
            'name' => 'BAMA Growth',
            'monthly_price' => 5000,
            'currency' => 'KES',
            'limits' => [],
            'is_active' => true,
        ]);

        $subscription = Subscription::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subMonth(),
            'renews_at' => now()->addDays(3),
        ]);

        return [$tenant, $plan, $subscription];
    }
}
