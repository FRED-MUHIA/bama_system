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

    public function test_mpesa_stk_push_trims_pasted_settings_before_sending_to_daraja(): void
    {
        Http::fake([
            'https://sandbox.safaricom.co.ke/oauth/v1/generate*' => Http::response([
                'access_token' => 'test-token',
            ]),
            'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest' => Http::response([
                'ResponseCode' => '0',
                'ResponseDescription' => 'Success. Request accepted for processing',
                'CheckoutRequestID' => 'ws_CO_trimmed',
                'MerchantRequestID' => 'mr_trimmed',
            ]),
        ]);

        $invoice = $this->mpesaFixture(
            mpesaConfig: [
                'shortcode' => ' 174379 ',
                'passkey' => " test-passkey\r\n",
                'callback_url' => ' https://bama.test/billing/mpesa/callback ',
                'transaction_type' => ' CustomerBuyGoodsOnline ',
            ],
            mpesaSetting: [
                'public_key' => ' consumer-key ',
                'secret_key' => " consumer-secret\n",
            ],
        );

        app(PaymentGatewayService::class)->mpesaStkPush($invoice, '0745506619');

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/oauth/v1/generate')) {
                return false;
            }

            return $request->hasHeader('Authorization', 'Basic '.base64_encode('consumer-key:consumer-secret'));
        });

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/mpesa/stkpush/')) {
                return false;
            }

            $data = $request->data();

            return $data['BusinessShortCode'] === '174379'
                && $data['PartyB'] === '174379'
                && $data['TransactionType'] === 'CustomerBuyGoodsOnline'
                && $data['CallBackURL'] === 'https://bama.test/billing/mpesa/callback'
                && $data['Password'] === base64_encode('174379test-passkey'.$data['Timestamp']);
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

        $this->assertDatabaseHas('subscription_payments', [
            'provider' => 'mpesa',
            'status' => 'failed',
            'failure_code' => '1',
        ]);
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

        $this->assertDatabaseHas('subscription_payments', [
            'provider' => 'mpesa',
            'status' => 'failed',
            'failure_code' => '400.002.02',
        ]);
    }

    public function test_paypal_checkout_converts_kes_invoice_to_usd_order(): void
    {
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'paypal-token',
            ]),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
                'id' => 'PAYPAL-ORDER-1',
                'links' => [[
                    'rel' => 'approve',
                    'href' => 'https://paypal.test/checkout/PAYPAL-ORDER-1',
                ]],
            ]),
        ]);

        $invoice = $this->mpesaFixture();
        $this->paypalSettingFixture(['kes_usd_rate' => '130']);

        $payment = app(PaymentGatewayService::class)->createPayPalOrder($invoice);

        $this->assertSame('requires_action', $payment->status);
        $this->assertSame('KES', $payment->currency);
        $this->assertSame('USD', data_get($payment->callback_payload, 'paypal_amount.currency'));
        $this->assertSame('0.08', data_get($payment->callback_payload, 'paypal_amount.value'));

        Http::assertSent(function ($request) {
            $data = $request->data();

            return str_contains($request->url(), '/v2/checkout/orders')
                && data_get($data, 'purchase_units.0.amount.currency_code') === 'USD'
                && data_get($data, 'purchase_units.0.amount.value') === '0.08';
        });
    }

    public function test_paypal_capture_posts_well_formed_empty_json_and_activates_after_completed_capture(): void
    {
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'paypal-token',
            ]),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders/PAYPAL-ORDER-2/capture' => Http::response([
                'status' => 'COMPLETED',
                'purchase_units' => [[
                    'payments' => [
                        'captures' => [[
                            'id' => 'CAPTURE-2',
                            'status' => 'COMPLETED',
                            'amount' => [
                                'currency_code' => 'USD',
                                'value' => '10.00',
                            ],
                        ]],
                    ],
                ]],
            ]),
        ]);

        $invoice = $this->mpesaFixture(currency: 'USD');
        $this->paypalSettingFixture();

        $payment = SubscriptionPayment::create([
            'subscription_invoice_id' => $invoice->id,
            'tenant_id' => $invoice->tenant_id,
            'provider' => 'paypal',
            'status' => 'requires_action',
            'amount' => $invoice->total,
            'currency' => $invoice->currency,
            'provider_order_id' => 'PAYPAL-ORDER-2',
            'callback_payload' => [
                'paypal_amount' => [
                    'currency' => 'USD',
                    'value' => '10.00',
                ],
            ],
        ]);

        $captured = app(PaymentGatewayService::class)->capturePayPalOrder($payment->provider_order_id);

        $this->assertSame('successful', $captured->status);
        $this->assertSame('CAPTURE-2', $captured->provider_receipt);
        $this->assertDatabaseHas('subscription_invoices', [
            'id' => $invoice->id,
            'status' => 'paid',
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/capture')
                && $request->method() === 'POST'
                && $request->body() === '{}';
        });
    }

    public function test_paypal_checkout_rejects_kes_invoice_without_exchange_rate(): void
    {
        Http::fake();

        $invoice = $this->mpesaFixture();
        $this->paypalSettingFixture();

        try {
            app(PaymentGatewayService::class)->createPayPalOrder($invoice);
            $this->fail('The PayPal checkout should have failed.');
        } catch (RuntimeException $e) {
            $this->assertSame(
                'PayPal KES to USD exchange rate is not configured in the owner console.',
                $e->getMessage()
            );
        }

        Http::assertNothingSent();
    }

    public function test_paypal_provider_422_is_reported_as_runtime_exception(): void
    {
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'paypal-token',
            ]),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
                'name' => 'UNPROCESSABLE_ENTITY',
                'details' => [[
                    'issue' => 'CURRENCY_NOT_SUPPORTED',
                    'description' => 'Currency code is not currently supported.',
                ]],
            ], 422),
        ]);

        $invoice = $this->mpesaFixture(currency: 'USD');
        $this->paypalSettingFixture();

        try {
            app(PaymentGatewayService::class)->createPayPalOrder($invoice);
            $this->fail('The PayPal checkout should have failed.');
        } catch (RuntimeException $e) {
            $this->assertSame(
                'PayPal request failed: Currency code is not currently supported.',
                $e->getMessage()
            );
        }
    }

    private function mpesaFixture(string $currency = 'KES', array $mpesaConfig = [], array $mpesaSetting = []): SubscriptionInvoice
    {
        PlatformPaymentSetting::updateOrCreate(
            ['provider' => 'mpesa'],
            array_merge([
                'is_enabled' => true,
                'mode' => 'sandbox',
                'public_key' => 'consumer-key',
                'secret_key' => 'consumer-secret',
                'config' => array_merge([
                    'shortcode' => '174379',
                    'passkey' => 'test-passkey',
                    'callback_url' => 'https://bama.test/billing/mpesa/callback',
                    'transaction_type' => 'CustomerPayBillOnline',
                ], $mpesaConfig),
            ], $mpesaSetting)
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
            'currency' => $currency,
            'subtotal' => 10,
            'total' => 10,
            'due_at' => now()->addDays(7),
            'metadata' => [],
        ]);
    }

    private function paypalSettingFixture(array $config = []): void
    {
        PlatformPaymentSetting::updateOrCreate(
            ['provider' => 'paypal'],
            [
                'is_enabled' => true,
                'mode' => 'sandbox',
                'public_key' => 'paypal-client',
                'secret_key' => 'paypal-secret',
                'config' => $config,
            ]
        );
    }
}
