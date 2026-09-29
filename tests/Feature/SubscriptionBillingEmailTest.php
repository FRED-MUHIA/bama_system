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
        Mail::assertSent(\App\Mail\SubscriptionReceiptMail::class, function ($mail) use ($payment) {
            $mail->build();

            return $mail->hasTo('billing@bama.test')
                && $mail->receipt['number'] === 'BAMA-RCT-'.$payment->subscription_invoice_id.'-'.$payment->id
                && count($mail->rawAttachments) === 1
                && str_starts_with($mail->rawAttachments[0]['data'], '%PDF-');
        });
        $this->assertNotEmpty($paidInvoice->fresh()->metadata['subscription_receipt']['renews_at']);
        $this->assertSame(0, $billing->sendInvoice($paidInvoice, 'paid'));
        Mail::assertSent(\App\Mail\SubscriptionReceiptMail::class, 1);
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

    #[\PHPUnit\Framework\Attributes\DataProvider('monthlyPaymentCases')]
    public function test_monthly_access_uses_each_customers_payment_date(string $path, string $status, ?string $trialEnd, ?string $renewal, string $expected): void
    {
        Mail::fake();
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-02-20 10:00:00'));
        [, $plan, $subscription] = $this->subscriptionFixture();
        $subscription->forceFill([
            'status' => $status,
            'trial_ends_at' => $trialEnd,
            'renews_at' => $renewal,
        ])->save();
        $billing = app(SubscriptionBillingService::class);
        $invoice = $billing->createInvoice($subscription, $plan);
        $payment = SubscriptionPayment::create([
            'subscription_invoice_id' => $invoice->id,
            'tenant_id' => $invoice->tenant_id,
            'provider' => 'manual',
            'status' => $path === 'verified' ? 'successful' : 'initiated',
            'amount' => $invoice->total,
            'currency' => $invoice->currency,
            'paid_at' => now(),
        ]);

        if ($path === 'verified') {
            app(\App\Services\Payments\SubscriptionPaymentService::class)->activateAfterVerifiedPayment($payment);
        } else {
            $billing->markPaid($payment);
        }

        $this->assertSame($expected, $subscription->fresh()->renews_at->format('Y-m-d H:i:s'));
        $this->assertSame('active', $subscription->fresh()->status);
    }

    public function test_existing_subscription_repair_previews_and_applies_once(): void
    {
        [, $plan, $subscription] = $this->subscriptionFixture();
        $subscription->update(['renews_at' => '2026-03-28 10:00:00']);
        $invoice = app(SubscriptionBillingService::class)->createInvoice($subscription, $plan);
        $payment = SubscriptionPayment::create([
            'subscription_invoice_id' => $invoice->id,
            'tenant_id' => $invoice->tenant_id,
            'provider' => 'manual',
            'status' => 'paid',
            'amount' => $invoice->total,
            'currency' => $invoice->currency,
            'paid_at' => '2026-02-20 10:00:00',
        ]);
        $invoice->markPaid($payment);
        $options = ['--tenant' => $subscription->tenant_id];

        $this->artisan('subscriptions:repair-periods', $options)->assertSuccessful();
        $this->assertSame('2026-03-28 10:00:00', $subscription->fresh()->renews_at->toDateTimeString());
        $this->artisan('subscriptions:repair-periods', $options + ['--apply' => true])->assertSuccessful();
        $this->assertSame('2026-03-22 10:00:00', $subscription->fresh()->renews_at->toDateTimeString());
        $this->artisan('subscriptions:repair-periods', $options + ['--apply' => true])->assertSuccessful();
        $this->assertCount(1, $subscription->fresh()->metadata['period_repairs']);
    }

    public static function monthlyPaymentCases(): array
    {
        $cases = [];
        foreach (['manual', 'verified'] as $path) {
            $cases[$path.' expired trial'] = [$path, 'trialing', '2026-02-18', null, '2026-03-22 10:00:00'];
            $cases[$path.' legacy trial'] = [$path, 'past_due', '2026-02-18', '2026-03-04', '2026-03-22 10:00:00'];
            $cases[$path.' late renewal'] = [$path, 'paused', null, '2026-02-10', '2026-03-22 10:00:00'];
            $cases[$path.' early renewal'] = [$path, 'active', null, '2026-02-25 10:00:00', '2026-03-27 10:00:00'];
        }

        return $cases;
    }

    public function test_legacy_unpaid_trial_stays_on_trial_expiry_after_entering_grace(): void
    {
        Mail::fake();
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-09-15 10:00:00'));
        [$tenant, , $subscription] = $this->subscriptionFixture();
        $subscription->update([
            'status' => 'trialing', 'trial_ends_at' => now()->subDay(),
            'renews_at' => now()->addDays(15),
        ]);
        $billing = app(SubscriptionBillingService::class);
        $billing->sweep();
        $this->assertSame('past_due', $subscription->fresh()->status);
        $manager = app(SubscriptionManager::class);
        $this->assertSame('grace', $manager->billingState($tenant->fresh(['subscription']))['state']);

        $this->travelTo(now()->addDay());
        $this->assertFalse($manager->active($tenant->fresh(['subscription'])));
        $this->assertSame('suspended', $tenant->fresh()->status);
        $this->assertSame('paused', $subscription->fresh()->status);
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
