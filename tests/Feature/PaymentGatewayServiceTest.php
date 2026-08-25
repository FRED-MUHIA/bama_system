<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\PlatformPaymentSetting;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Services\Billing\PaymentGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class PaymentGatewayServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_mpesa_stk_push_sends_daraja_safe_payload(): void
    {
        Http::fake([
            'https://sandbox.safaricom.co.ke/oauth/v1/generate*' => Http::response([
                'access_token' => 'test-token',
            ]),
            'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest' => Http::response([
                'ResponseCode' => '0',
                'ResponseDescription' => 'Success. Request accepted for processing',
                'CheckoutRequestID' => 'ws_CO_test',
                'MerchantRequestID' => 'mr_test',
            ]),
        ]);

        $invoice = $this->mpesaFixture();

        $payment = app(PaymentGatewayService::class)->mpesaStkPush($invoice, '0712 345 678');

        $this->assertSame('pending', $payment->status);
        $this->assertSame('254712345678', $payment->phone);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return str_contains($request->url(), '/mpesa/stkpush/')
                && $data['PhoneNumber'] === '254712345678'
                && $data['AccountReference'] === 'BAMA-2026082'
                && $data['TransactionDesc'] === 'BAMA Invoice'
                && strlen($data['TransactionDesc']) <= 13;
        });
    }

    public function test_mpesa_stk_push_accepts_prefilled_kenyan_phone_number(): void
    {
        Http::fake([
            'https://sandbox.safaricom.co.ke/oauth/v1/generate*' => Http::response([
                'access_token' => 'test-token',
            ]),
            'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest' => Http::response([
                'ResponseCode' => '0',
                'ResponseDescription' => 'Success. Request accepted for processing',
                'CheckoutRequestID' => 'ws_CO_prefilled',
                'MerchantRequestID' => 'mr_prefilled',
            ]),
        ]);

        $invoice = $this->mpesaFixture();

        $payment = app(PaymentGatewayService::class)->mpesaStkPush($invoice, '254745506619');

        $this->assertSame('pending', $payment->status);
        $this->assertSame('254745506619', $payment->phone);
    }

    public function test_mpesa_stk_push_accepts_local_phone_number_format(): void
    {
        Http::fake([
            'https://sandbox.safaricom.co.ke/oauth/v1/generate*' => Http::response([
                'access_token' => 'test-token',
            ]),
            'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest' => Http::response([
                'ResponseCode' => '0',
                'ResponseDescription' => 'Success. Request accepted for processing',
                'CheckoutRequestID' => 'ws_CO_local',
                'MerchantRequestID' => 'mr_local',
            ]),
        ]);

        $invoice = $this->mpesaFixture();

        $payment = app(PaymentGatewayService::class)->mpesaStkPush($invoice, '0745506619');

        $this->assertSame('pending', $payment->status);
        $this->assertSame('254745506619', $payment->phone);
    }

    public function test_mpesa_stk_push_rejects_overlong_phone_number(): void
    {
        $invoice = $this->mpesaFixture();

        try {
            app(PaymentGatewayService::class)->mpesaStkPush($invoice, '2547588088713');
            $this->fail('The M-PESA phone number should have failed validation.');
        } catch (RuntimeException $e) {
            $this->assertSame(
                'Enter a valid M-PESA phone number: 10 digits like 0700000000 or 12 digits like 254700000000.',
                $e->getMessage()
            );
        }
    }

    public function test_mpesa_stk_push_rejects_non_zero_daraja_response(): void
    {
        Http::fake([
            'https://sandbox.safaricom.co.ke/oauth/v1/generate*' => Http::response([
                'access_token' => 'test-token',
            ]),
            'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest' => Http::response([
                'ResponseCode' => '1',
                'ResponseDescription' => 'Unable to lock subscriber, a transaction is already in process',
            ]),
        ]);

        $invoice = $this->mpesaFixture();

        try {
            app(PaymentGatewayService::class)->mpesaStkPush($invoice, '0745506619');
            $this->fail('The M-PESA request should have failed.');
        } catch (RuntimeException $e) {
            $this->assertSame(
                'M-PESA request failed: Unable to lock subscriber, a transaction is already in process',
                $e->getMessage()
            );
        }

        $this->assertDatabaseCount('subscription_payments', 0);
    }

    public function test_mpesa_status_query_records_timeout_failure(): void
    {
        Http::fake([
            'https://sandbox.safaricom.co.ke/oauth/v1/generate*' => Http::response([
                'access_token' => 'test-token',
            ]),
            'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query' => Http::response([
                'ResponseCode' => '0',
                'ResponseDescription' => 'The service request has been accepted successfully',
                'MerchantRequestID' => 'mr_timeout',
                'CheckoutRequestID' => 'ws_CO_timeout',
                'ResultCode' => '1037',
                'ResultDesc' => 'DS timeout user cannot be reached',
            ]),
        ]);

        $invoice = $this->mpesaFixture();
        $payment = SubscriptionPayment::create([
            'subscription_invoice_id' => $invoice->id,
            'tenant_id' => $invoice->tenant_id,
            'provider' => 'mpesa',
            'status' => 'pending',
            'amount' => $invoice->total,
            'currency' => $invoice->currency,
            'checkout_request_id' => 'ws_CO_timeout',
            'merchant_request_id' => 'mr_timeout',
            'phone' => '254745506619',
            'callback_payload' => ['ResponseDescription' => 'Success. Request accepted for processing'],
        ]);

        $checked = app(PaymentGatewayService::class)->queryMpesaStatus($payment);

        $this->assertSame('failed', $checked->status);
        $this->assertSame('DS timeout user cannot be reached', data_get($checked->callback_payload, 'stk_query.ResultDesc'));
    }

    public function test_mpesa_status_query_rejects_non_zero_daraja_response(): void
    {
        Http::fake([
            'https://sandbox.safaricom.co.ke/oauth/v1/generate*' => Http::response([
                'access_token' => 'test-token',
            ]),
            'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query' => Http::response([
                'ResponseCode' => '1',
                'ResponseDescription' => 'The transaction is being processed',
            ]),
        ]);

        $invoice = $this->mpesaFixture();
        $payment = SubscriptionPayment::create([
            'subscription_invoice_id' => $invoice->id,
            'tenant_id' => $invoice->tenant_id,
            'provider' => 'mpesa',
            'status' => 'pending',
            'amount' => $invoice->total,
            'currency' => $invoice->currency,
            'checkout_request_id' => 'ws_CO_pending',
        ]);

        try {
            app(PaymentGatewayService::class)->queryMpesaStatus($payment);
            $this->fail('The M-PESA status check should have failed.');
        } catch (RuntimeException $e) {
            $this->assertSame('M-PESA status check failed: The transaction is being processed', $e->getMessage());
        }
    }

    public function test_mpesa_provider_400_is_reported_as_runtime_exception(): void
    {
        Http::fake([
            'https://sandbox.safaricom.co.ke/oauth/v1/generate*' => Http::response([
                'access_token' => 'test-token',
            ]),
            'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest' => Http::response([
                'requestId' => 'b04e-test',
                'errorCode' => '400.002.02',
                'errorMessage' => 'Bad Request - Invalid PhoneNumber',
            ], 400),
        ]);

        $invoice = $this->mpesaFixture();

        try {
            app(PaymentGatewayService::class)->mpesaStkPush($invoice, '0712345678');
            $this->fail('The M-PESA request should have failed.');
        } catch (RuntimeException $e) {
            $this->assertSame('M-PESA request failed: Bad Request - Invalid PhoneNumber', $e->getMessage());
        }

        $this->assertDatabaseCount('subscription_payments', 0);
    }

    private function mpesaFixture(): SubscriptionInvoice
    {
        PlatformPaymentSetting::updateOrCreate(
            ['provider' => 'mpesa'],
            [
                'is_enabled' => true,
                'mode' => 'sandbox',
                'public_key' => 'consumer-key',
                'secret_key' => 'consumer-secret',
                'config' => [
                    'shortcode' => '174379',
                    'passkey' => 'test-passkey',
                    'callback_url' => 'https://bama.test/billing/mpesa/callback',
                    'transaction_type' => 'CustomerPayBillOnline',
                ],
            ]
        );

        $tenant = Tenant::create([
            'name' => 'BAMA Test',
            'slug' => 'bama-test-'.str()->random(8),
            'industry' => 'printing',
            'status' => 'active',
        ]);

        $plan = Plan::create([
            'slug' => 'bama-growth-test-'.str()->random(8),
            'name' => 'BAMA Growth',
            'monthly_price' => 10,
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

        return SubscriptionInvoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'plan_id' => $plan->id,
            'invoice_number' => 'BAMA-20260825-2-B71J1',
            'billing_email' => 'billing@bama.test',
            'customer_name' => 'BAMA Prints Test',
            'status' => 'sent',
            'currency' => 'KES',
            'subtotal' => 10,
            'total' => 10,
            'due_at' => now()->addDays(7),
            'metadata' => [],
        ]);
    }
}
