<?php

namespace App\Services\Billing;

use App\Enums\PaymentStatus;
use App\Models\PaymentWebhookEvent;
use App\Models\PlatformPaymentSetting;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Services\ExchangeRateService;
use App\Services\Payments\PaymentAuditService;
use App\Services\Payments\SubscriptionPaymentService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class PaymentGatewayService
{
    public function __construct(
        private readonly SubscriptionPaymentService $payments,
        private readonly PaymentAuditService $audit,
        private readonly ExchangeRateService $exchangeRates,
    ) {}

    public function mpesaStkPush(SubscriptionInvoice $invoice, string $phone): SubscriptionPayment
    {
        $mpesa = $this->mpesaConfig();
        $timestamp = now()->format('YmdHis');
        $normalizedPhone = $this->normalizeMpesaPhone($phone);
        $amount = max(1, (int) round((float) $invoice->total));
        $accountReference = Str::limit($invoice->invoice_number, 12, '');
        $payload = [
            'BusinessShortCode' => $mpesa['shortcode'],
            'Password' => $this->mpesaPassword($mpesa, $timestamp),
            'Timestamp' => $timestamp,
            'TransactionType' => $mpesa['transaction_type'],
            'Amount' => $amount,
            'PartyA' => $normalizedPhone,
            'PartyB' => $mpesa['shortcode'],
            'PhoneNumber' => $normalizedPhone,
            'CallBackURL' => $mpesa['callback_url'],
            'AccountReference' => $accountReference,
            'TransactionDesc' => 'Bama Invoice',
        ];

        $payment = $invoice->payments()->create([
            'tenant_id' => $invoice->tenant_id,
            'merchant_reference' => $this->merchantReference($invoice),
            'provider' => 'mpesa',
            'status' => PaymentStatus::Created->value,
            'amount' => $invoice->total,
            'currency' => $invoice->currency,
            'phone' => $normalizedPhone,
            'request_payload' => $this->safeMpesaPayload($payload, $mpesa),
            'initiated_at' => now(),
            'callback_payload' => [
                'normalized_request' => $this->mpesaDiagnostics($mpesa, $amount, $normalizedPhone, $accountReference, $timestamp),
            ],
        ]);
        $this->audit->record($payment, 'payment_created');

        try {
            $response = Http::withToken($this->mpesaAccessToken($mpesa, 'authorization'))
                ->acceptJson()
                ->timeout(30)
                ->post($mpesa['base_url'].'/mpesa/stkpush/v1/processrequest', $payload)
                ->throw()
                ->json();
        } catch (RequestException $e) {
            $this->failPaymentFromException($payment, $e, 'mpesa_stk_push');
            throw $this->mpesaRequestException($e, 'stk_push');
        }

        try {
            $this->assertMpesaStkAccepted($response);
        } catch (RuntimeException $e) {
            $this->payments->transition($payment, PaymentStatus::Failed, [
                'response_payload' => $this->audit->sanitize($response),
                'failure_code' => $response['ResponseCode'] ?? null,
                'failure_message' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $this->payments->transition($payment, PaymentStatus::Pending, [
            'checkout_request_id' => $response['CheckoutRequestID'] ?? null,
            'merchant_request_id' => $response['MerchantRequestID'] ?? null,
            'response_payload' => $this->audit->sanitize($response),
            'callback_payload' => $response + [
                'normalized_request' => $this->mpesaDiagnostics($mpesa, $amount, $normalizedPhone, $accountReference, $timestamp),
            ],
        ]);
    }

    public function handleMpesaCallback(array $payload): ?SubscriptionPayment
    {
        $stk = data_get($payload, 'Body.stkCallback', []);
        $checkoutRequestId = $stk['CheckoutRequestID'] ?? null;
        $event = $this->storeWebhookEvent('mpesa', $checkoutRequestId, 'stk.callback', $payload, true);

        if (! $checkoutRequestId || ! Schema::hasTable('subscription_payments')) {
            $event?->forceFill(['processed' => true, 'processed_at' => now(), 'processing_error' => 'Missing checkout request ID'])->save();

            return null;
        }

        $payment = SubscriptionPayment::where('checkout_request_id', $checkoutRequestId)->latest()->first();
        if (! $payment) {
            $event?->forceFill(['processed' => true, 'processed_at' => now(), 'processing_error' => 'No matching payment'])->save();

            return null;
        }

        if ($payment->isSuccessful() && $payment->processed_at) {
            $event?->forceFill(['processed' => true, 'processed_at' => now()])->save();

            return $payment->refresh();
        }

        $items = collect(data_get($stk, 'CallbackMetadata.Item', []))->pluck('Value', 'Name');
        $resultCode = (int) ($stk['ResultCode'] ?? -1);
        $callbackPayload = array_merge($payment->callback_payload ?? [], ['callback' => $payload]);

        if ($resultCode !== 0) {
            $status = str_contains(strtolower((string) ($stk['ResultDesc'] ?? '')), 'cancel')
                ? PaymentStatus::Cancelled
                : PaymentStatus::Failed;

            $updated = $this->payments->transition($payment, $status, [
                'callback_payload' => $callbackPayload,
                'failure_code' => (string) $resultCode,
                'failure_message' => $stk['ResultDesc'] ?? 'M-PESA callback reported a failed payment.',
            ]);
            $event?->forceFill(['processed' => true, 'processed_at' => now()])->save();

            return $updated;
        }

        $paidAmount = (float) ($items['Amount'] ?? 0);
        $expectedAmount = (float) max(1, (int) round((float) $payment->amount));

        if ($paidAmount !== $expectedAmount) {
            $updated = $this->payments->transition($payment, PaymentStatus::Failed, [
                'callback_payload' => $callbackPayload,
                'failure_code' => 'amount_mismatch',
                'failure_message' => "M-PESA callback amount {$paidAmount} did not match expected {$expectedAmount}.",
            ]);
            $event?->forceFill(['processed' => true, 'processed_at' => now(), 'processing_error' => 'Amount mismatch'])->save();

            return $updated;
        }

        $updated = $this->payments->transition($payment, PaymentStatus::Successful, [
            'provider_receipt' => $items['MpesaReceiptNumber'] ?? null,
            'provider_payment_id' => $items['MpesaReceiptNumber'] ?? $checkoutRequestId,
            'phone' => $items['PhoneNumber'] ?? $payment->phone,
            'callback_payload' => $callbackPayload,
        ]);

        $this->payments->activateAfterVerifiedPayment($updated, [
            'gateway' => 'mpesa',
            'checkout_request_id' => $checkoutRequestId,
            'receipt' => $updated->provider_receipt,
        ]);

        $event?->forceFill(['processed' => true, 'processed_at' => now()])->save();

        return $updated->refresh();
    }

    public function queryMpesaStatus(SubscriptionPayment $payment): SubscriptionPayment
    {
        if ($payment->provider !== 'mpesa') {
            throw new RuntimeException('Only M-PESA payments can be checked through Daraja.');
        }

        if (! $payment->checkout_request_id) {
            throw new RuntimeException('This M-PESA payment does not have a checkout request ID.');
        }

        if ($payment->isSuccessful() && $payment->processed_at) {
            return $payment->refresh();
        }

        $mpesa = $this->mpesaConfig();
        $timestamp = now()->format('YmdHis');

        try {
            $response = Http::withToken($this->mpesaAccessToken($mpesa, 'status_authorization'))
                ->acceptJson()
                ->timeout(30)
                ->post($mpesa['base_url'].'/mpesa/stkpushquery/v1/query', [
                    'BusinessShortCode' => $mpesa['shortcode'],
                    'Password' => $this->mpesaPassword($mpesa, $timestamp),
                    'Timestamp' => $timestamp,
                    'CheckoutRequestID' => $payment->checkout_request_id,
                ])
                ->throw()
                ->json();
        } catch (RequestException $e) {
            throw $this->mpesaRequestException($e, 'stk_query');
        }

        $this->assertMpesaStatusAccepted($response);

        $payload = $payment->callback_payload ?? [];
        $payload['stk_query'] = $this->audit->sanitize($response);
        $resultCode = array_key_exists('ResultCode', $response) ? (int) $response['ResultCode'] : null;

        if ($resultCode === null) {
            $payment->forceFill(['callback_payload' => $payload, 'response_payload' => $this->audit->sanitize($response)])->save();

            return $payment->refresh();
        }

        if ($resultCode !== 0) {
            $status = str_contains(strtolower((string) ($response['ResultDesc'] ?? '')), 'cancel')
                ? PaymentStatus::Cancelled
                : PaymentStatus::Failed;

            return $this->payments->transition($payment, $status, [
                'callback_payload' => $payload,
                'response_payload' => $this->audit->sanitize($response),
                'failure_code' => (string) $resultCode,
                'failure_message' => $response['ResultDesc'] ?? 'M-PESA status query reported a failed payment.',
            ]);
        }

        $updated = $this->payments->transition($payment, PaymentStatus::Successful, [
            'provider_payment_id' => $payment->provider_payment_id ?: $payment->checkout_request_id,
            'provider_receipt' => $payment->provider_receipt ?: $payment->checkout_request_id,
            'callback_payload' => $payload,
            'response_payload' => $this->audit->sanitize($response),
        ]);

        $this->payments->activateAfterVerifiedPayment($updated, [
            'gateway' => 'mpesa',
            'checkout_request_id' => $payment->checkout_request_id,
            'source' => 'stk_query',
        ]);

        return $updated->refresh();
    }

    public function createPayPalOrder(SubscriptionInvoice $invoice): SubscriptionPayment
    {
        $setting = $this->setting('paypal');
        $config = $setting->config ?? [];
        $clientId = $setting->public_key ?: config('services.paypal.client_id');
        $secret = $setting->secret_key ?: config('services.paypal.secret');
        $currency = strtoupper($invoice->currency);
        $paypalAmount = $this->paypalCheckoutAmount($invoice, $config);

        if (! $clientId || ! $secret) {
            throw new RuntimeException('PayPal is not fully configured in the owner console.');
        }

        $payment = $invoice->payments()->create([
            'tenant_id' => $invoice->tenant_id,
            'merchant_reference' => $this->merchantReference($invoice),
            'provider' => 'paypal',
            'status' => PaymentStatus::Created->value,
            'amount' => $invoice->total,
            'currency' => $invoice->currency,
            'initiated_at' => now(),
        ]);

        $baseUrl = $this->paypalBaseUrl($setting->mode);
        $token = $this->paypalAccessToken($baseUrl, $clientId, $secret, 'authorization');
        $orderPayload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $invoice->invoice_number,
                'custom_id' => $payment->merchant_reference,
                'invoice_id' => $invoice->invoice_number,
                'description' => 'Bama '.$invoice->plan?->name.' subscription',
                'amount' => [
                    'currency_code' => $paypalAmount['currency'],
                    'value' => $paypalAmount['value'],
                ],
            ]],
            'application_context' => [
                'brand_name' => 'Bama Solutions',
                'user_action' => 'PAY_NOW',
                'return_url' => route('billing.paypal.return'),
                'cancel_url' => route('billing.paypal.cancel'),
            ],
        ];

        try {
            $order = Http::withToken($token)
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->post($baseUrl.'/v2/checkout/orders', $orderPayload)
                ->throw()
                ->json();
        } catch (RequestException $e) {
            $this->failPaymentFromException($payment, $e, 'paypal_create_order');
            throw $this->paypalRequestException($e, 'create_order');
        }

        $approvalUrl = collect($order['links'] ?? [])->firstWhere('rel', 'approve')['href'] ?? null;

        return $this->payments->transition($payment, PaymentStatus::RequiresAction, [
            'provider_order_id' => $order['id'] ?? null,
            'payment_url' => $approvalUrl,
            'request_payload' => $this->audit->sanitize($orderPayload),
            'response_payload' => $this->audit->sanitize($order),
            'callback_payload' => [
                'order' => $this->audit->sanitize($order),
                'paypal_amount' => $paypalAmount,
                'invoice_amount' => [
                    'currency' => $currency,
                    'value' => number_format((float) $invoice->total, 2, '.', ''),
                ],
            ],
        ]);
    }

    public function capturePayPalOrder(string $orderId): SubscriptionPayment
    {
        $payment = SubscriptionPayment::where('provider', 'paypal')->where('provider_order_id', $orderId)->latest()->firstOrFail();

        if ($payment->isSuccessful() && $payment->processed_at) {
            return $payment->refresh();
        }

        $setting = $this->setting('paypal');
        $clientId = $setting->public_key ?: config('services.paypal.client_id');
        $secret = $setting->secret_key ?: config('services.paypal.secret');
        $baseUrl = $this->paypalBaseUrl($setting->mode);
        $token = $this->paypalAccessToken($baseUrl, $clientId, $secret, 'capture_authorization');

        try {
            $capture = Http::withToken($token)
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->post($baseUrl.'/v2/checkout/orders/'.$orderId.'/capture', new \stdClass)
                ->throw()
                ->json();
        } catch (RequestException $e) {
            throw $this->paypalRequestException($e, 'capture_order');
        }

        return $this->completePayPalCapture($payment, $capture, 'capture_return');
    }

    public function queryPayPalOrder(SubscriptionPayment $payment): SubscriptionPayment
    {
        if ($payment->provider !== 'paypal' || ! $payment->provider_order_id) {
            return $payment;
        }

        $setting = $this->setting('paypal');
        $clientId = $setting->public_key ?: config('services.paypal.client_id');
        $secret = $setting->secret_key ?: config('services.paypal.secret');
        $baseUrl = $this->paypalBaseUrl($setting->mode);
        $token = $this->paypalAccessToken($baseUrl, $clientId, $secret, 'order_lookup_authorization');

        try {
            $order = Http::withToken($token)
                ->acceptJson()
                ->timeout(30)
                ->get($baseUrl.'/v2/checkout/orders/'.$payment->provider_order_id)
                ->throw()
                ->json();
        } catch (RequestException $e) {
            throw $this->paypalRequestException($e, 'order_lookup');
        }

        $payload = $payment->callback_payload ?? [];
        $payload['order_lookup'] = $this->audit->sanitize($order);
        $payment->forceFill(['callback_payload' => $payload])->save();

        if (($order['status'] ?? null) === 'COMPLETED') {
            return $this->completePayPalCapture($payment, $order, 'order_lookup');
        }

        return $payment->refresh();
    }

    public function handlePayPalWebhook(Request $request): ?SubscriptionPayment
    {
        $payload = $request->json()->all();
        $eventId = $payload['id'] ?? null;
        $eventType = $payload['event_type'] ?? null;
        $signatureValid = $this->verifyPayPalWebhook($request, $payload);
        $event = $this->storeWebhookEvent('paypal', $eventId, $eventType, $payload, $signatureValid);

        if (! $signatureValid) {
            $event?->forceFill(['processed' => true, 'processed_at' => now(), 'processing_error' => 'Invalid PayPal webhook signature'])->save();

            return null;
        }

        $orderId = data_get($payload, 'resource.supplementary_data.related_ids.order_id');
        $captureId = data_get($payload, 'resource.id');
        $payment = SubscriptionPayment::where('provider', 'paypal')
            ->when($orderId, fn ($query) => $query->where('provider_order_id', $orderId))
            ->when(! $orderId && $captureId, fn ($query) => $query->where('provider_payment_id', $captureId))
            ->latest()
            ->first();

        if (! $payment) {
            $event?->forceFill(['processed' => true, 'processed_at' => now(), 'processing_error' => 'No matching payment'])->save();

            return null;
        }

        if ($eventType === 'PAYMENT.CAPTURE.COMPLETED') {
            $updated = $this->completePayPalCapture($payment, ['status' => 'COMPLETED', 'purchase_units' => [[
                'payments' => ['captures' => [$payload['resource']]],
            ]]], 'webhook');
            $event?->forceFill(['processed' => true, 'processed_at' => now()])->save();

            return $updated;
        }

        if (in_array($eventType, ['PAYMENT.CAPTURE.DENIED', 'PAYMENT.CAPTURE.DECLINED'], true)) {
            $updated = $this->payments->transition($payment, PaymentStatus::Failed, [
                'callback_payload' => array_merge($payment->callback_payload ?? [], ['paypal_webhook' => $this->audit->sanitize($payload)]),
                'failure_code' => $eventType,
                'failure_message' => data_get($payload, 'resource.status_details.reason', 'PayPal capture was denied.'),
            ]);
            $event?->forceFill(['processed' => true, 'processed_at' => now()])->save();

            return $updated;
        }

        $event?->forceFill(['processed' => true, 'processed_at' => now()])->save();

        return $payment->refresh();
    }

    public function createStripePaymentIntent(SubscriptionInvoice $invoice): SubscriptionPayment
    {
        $setting = $this->setting('card');
        $secret = $setting->secret_key ?: config('services.stripe.secret');
        $publicKey = $setting->public_key ?: config('services.stripe.key');

        if (! $secret || ! $publicKey) {
            throw new RuntimeException('Card payments are not fully configured in the owner console.');
        }

        $payment = $invoice->payments()->create([
            'tenant_id' => $invoice->tenant_id,
            'merchant_reference' => $this->merchantReference($invoice),
            'provider' => 'card',
            'status' => PaymentStatus::Created->value,
            'amount' => $invoice->total,
            'currency' => $invoice->currency,
            'initiated_at' => now(),
        ]);

        $payload = [
            'amount' => (int) round(((float) $invoice->total) * 100),
            'currency' => strtolower($invoice->currency),
            'automatic_payment_methods' => ['enabled' => 'true'],
            'metadata' => [
                'subscription_payment_id' => $payment->id,
                'tenant_id' => $invoice->tenant_id,
                'subscription_id' => $invoice->subscription_id,
                'plan_id' => $invoice->plan_id,
                'merchant_reference' => $payment->merchant_reference,
            ],
        ];

        try {
            $intent = Http::asForm()
                ->withToken($secret)
                ->timeout(30)
                ->post('https://api.stripe.com/v1/payment_intents', $payload)
                ->throw()
                ->json();
        } catch (RequestException $e) {
            $this->failPaymentFromException($payment, $e, 'stripe_payment_intent');
            throw new RuntimeException('Card payment request failed. Please try another method.', previous: $e);
        }

        return $this->payments->transition($payment, PaymentStatus::RequiresAction, [
            'provider_order_id' => $intent['id'] ?? null,
            'provider_payment_id' => $intent['id'] ?? null,
            'request_payload' => $this->audit->sanitize($payload),
            'response_payload' => $this->audit->sanitize($intent),
            'callback_payload' => ['stripe_intent' => $this->audit->sanitize($intent)],
        ]);
    }

    public function handleStripeWebhook(Request $request): ?SubscriptionPayment
    {
        $payload = json_decode($request->getContent(), true) ?: [];
        $eventId = $payload['id'] ?? null;
        $eventType = $payload['type'] ?? null;
        $signatureValid = $this->verifyStripeWebhook($request);
        $event = $this->storeWebhookEvent('stripe', $eventId, $eventType, $payload, $signatureValid);

        if (! $signatureValid) {
            $event?->forceFill(['processed' => true, 'processed_at' => now(), 'processing_error' => 'Invalid Stripe signature'])->save();

            return null;
        }

        $intent = data_get($payload, 'data.object');
        $paymentId = data_get($intent, 'metadata.subscription_payment_id');
        $payment = $paymentId ? SubscriptionPayment::find($paymentId) : null;

        if (! $payment) {
            $event?->forceFill(['processed' => true, 'processed_at' => now(), 'processing_error' => 'No matching payment'])->save();

            return null;
        }

        if ($eventType === 'payment_intent.succeeded') {
            $updated = $this->payments->transition($payment, PaymentStatus::Successful, [
                'provider_payment_id' => $intent['id'] ?? $payment->provider_payment_id,
                'provider_receipt' => $intent['latest_charge'] ?? $intent['id'] ?? null,
                'callback_payload' => array_merge($payment->callback_payload ?? [], ['stripe_webhook' => $this->audit->sanitize($payload)]),
            ]);
            $this->payments->activateAfterVerifiedPayment($updated, ['gateway' => 'stripe', 'event_id' => $eventId]);
            $event?->forceFill(['processed' => true, 'processed_at' => now()])->save();

            return $updated->refresh();
        }

        if (in_array($eventType, ['payment_intent.payment_failed', 'payment_intent.canceled'], true)) {
            $status = $eventType === 'payment_intent.canceled' ? PaymentStatus::Cancelled : PaymentStatus::Failed;
            $updated = $this->payments->transition($payment, $status, [
                'callback_payload' => array_merge($payment->callback_payload ?? [], ['stripe_webhook' => $this->audit->sanitize($payload)]),
                'failure_code' => data_get($intent, 'last_payment_error.code'),
                'failure_message' => data_get($intent, 'last_payment_error.message', 'Card payment was not completed.'),
            ]);
            $event?->forceFill(['processed' => true, 'processed_at' => now()])->save();

            return $updated;
        }

        $event?->forceFill(['processed' => true, 'processed_at' => now()])->save();

        return $payment->refresh();
    }

    public function cardCheckout(SubscriptionInvoice $invoice): SubscriptionPayment
    {
        return $this->createStripePaymentIntent($invoice);
    }

    public function setting(string $provider): PlatformPaymentSetting
    {
        $setting = PlatformPaymentSetting::firstOrCreate(
            ['provider' => $provider],
            ['mode' => 'sandbox', 'is_enabled' => false, 'config' => []]
        );

        if (! $setting->is_enabled) {
            throw new RuntimeException(strtoupper($provider).' payments are not enabled in the owner console.');
        }

        return $setting;
    }

    private function completePayPalCapture(SubscriptionPayment $payment, array $capture, string $source): SubscriptionPayment
    {
        $captureNode = data_get($capture, 'purchase_units.0.payments.captures.0', []);
        $status = $capture['status'] ?? data_get($captureNode, 'status');
        $captureId = data_get($captureNode, 'id') ?? data_get($capture, 'id');

        if ($status !== 'COMPLETED' || ! $captureId) {
            return $this->payments->transition($payment, PaymentStatus::Processing, [
                'callback_payload' => array_merge($payment->callback_payload ?? [], ['paypal_capture' => $this->audit->sanitize($capture)]),
            ]);
        }

        $expected = data_get($payment->callback_payload, 'paypal_amount');
        $capturedCurrency = data_get($captureNode, 'amount.currency_code');
        $capturedValue = data_get($captureNode, 'amount.value');

        if ($expected && ($capturedCurrency !== $expected['currency'] || $capturedValue !== $expected['value'])) {
            return $this->payments->transition($payment, PaymentStatus::Failed, [
                'callback_payload' => array_merge($payment->callback_payload ?? [], ['paypal_capture' => $this->audit->sanitize($capture)]),
                'failure_code' => 'amount_mismatch',
                'failure_message' => 'PayPal capture amount or currency did not match the created order.',
            ]);
        }

        $updated = $this->payments->transition($payment, PaymentStatus::Successful, [
            'provider_payment_id' => $captureId,
            'provider_receipt' => $captureId,
            'callback_payload' => array_merge($payment->callback_payload ?? [], ['paypal_capture' => $this->audit->sanitize($capture)]),
        ]);

        $this->payments->activateAfterVerifiedPayment($updated, [
            'gateway' => 'paypal',
            'source' => $source,
            'capture_id' => $captureId,
        ]);

        return $updated->refresh();
    }

    private function mpesaBaseUrl(string $mode): string
    {
        return $mode === 'live' ? 'https://api.safaricom.co.ke' : 'https://sandbox.safaricom.co.ke';
    }

    private function mpesaConfig(): array
    {
        $setting = $this->setting('mpesa');
        $config = $setting->config ?? [];
        $transactionType = trim((string) ($config['transaction_type'] ?? config('services.mpesa.transaction_type', 'CustomerPayBillOnline')));
        $callbackUrl = trim((string) ($config['callback_url'] ?? config('services.mpesa.callback_url') ?? route('api.payments.mpesa.callback')));
        $mode = $setting->mode === 'live' ? 'live' : config('services.mpesa.environment', 'sandbox');

        $mpesa = [
            'mode' => $mode === 'live' ? 'live' : 'sandbox',
            'base_url' => $this->mpesaBaseUrl($mode),
            'consumer_key' => trim((string) ($setting->public_key ?: config('services.mpesa.consumer_key'))),
            'consumer_secret' => trim((string) ($setting->secret_key ?: config('services.mpesa.consumer_secret'))),
            'shortcode' => trim((string) ($config['shortcode'] ?? config('services.mpesa.shortcode'))),
            'passkey' => trim((string) ($config['passkey'] ?? config('services.mpesa.passkey'))),
            'callback_url' => $callbackUrl,
            'transaction_type' => $transactionType,
        ];

        if (! $mpesa['consumer_key'] || ! $mpesa['consumer_secret'] || ! $mpesa['shortcode'] || ! $mpesa['passkey']) {
            throw new RuntimeException('M-PESA STK is not fully configured in the owner console.');
        }

        if (! preg_match('/^\d+$/', $mpesa['shortcode'])) {
            throw new RuntimeException('Enter a numeric M-PESA PayBill or Till shortcode.');
        }

        if (! in_array($mpesa['transaction_type'], ['CustomerPayBillOnline', 'CustomerBuyGoodsOnline'], true)) {
            throw new RuntimeException('Choose a valid M-PESA transaction type: PayBill or Buy Goods.');
        }

        if (! filter_var($mpesa['callback_url'], FILTER_VALIDATE_URL)) {
            throw new RuntimeException('Enter a valid HTTPS M-PESA callback URL.');
        }

        if (! str_starts_with($mpesa['callback_url'], 'https://')) {
            throw new RuntimeException('M-PESA callback URL must use HTTPS.');
        }

        return $mpesa;
    }

    private function mpesaAccessToken(array $mpesa, string $stage): string
    {
        $cacheKey = 'payments.mpesa.token.'.sha1($mpesa['mode'].'|'.$mpesa['consumer_key']);

        if (app()->runningUnitTests()) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($mpesa, $stage) {
            try {
                $token = Http::withBasicAuth($mpesa['consumer_key'], $mpesa['consumer_secret'])
                    ->timeout(30)
                    ->get($mpesa['base_url'].'/oauth/v1/generate', ['grant_type' => 'client_credentials'])
                    ->throw()
                    ->json('access_token');
            } catch (RequestException $e) {
                throw $this->mpesaRequestException($e, $stage);
            }

            if (blank($token)) {
                throw new RuntimeException('M-PESA request failed: Safaricom did not return an access token.');
            }

            return $token;
        });
    }

    private function paypalAccessToken(string $baseUrl, string $clientId, string $secret, string $stage): string
    {
        $cacheKey = 'payments.paypal.token.'.sha1($baseUrl.'|'.$clientId);

        if (app()->runningUnitTests()) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addMinutes(45), function () use ($baseUrl, $clientId, $secret, $stage) {
            try {
                $token = Http::asForm()
                    ->withBasicAuth($clientId, $secret)
                    ->timeout(30)
                    ->post($baseUrl.'/v1/oauth2/token', ['grant_type' => 'client_credentials'])
                    ->throw()
                    ->json('access_token');
            } catch (RequestException $e) {
                throw $this->paypalRequestException($e, $stage);
            }

            if (blank($token)) {
                throw new RuntimeException('PayPal request failed: PayPal did not return an access token.');
            }

            return $token;
        });
    }

    private function mpesaPassword(array $mpesa, string $timestamp): string
    {
        return base64_encode($mpesa['shortcode'].$mpesa['passkey'].$timestamp);
    }

    private function assertMpesaStkAccepted(array $response): void
    {
        if ((string) ($response['ResponseCode'] ?? '') !== '0') {
            throw new RuntimeException(
                'M-PESA request failed: '.($response['ResponseDescription'] ?? $response['errorMessage'] ?? 'Safaricom did not accept the STK request.')
            );
        }

        if (blank($response['CheckoutRequestID'] ?? null)) {
            throw new RuntimeException('M-PESA request failed: Safaricom did not return a checkout request ID.');
        }
    }

    private function assertMpesaStatusAccepted(array $response): void
    {
        if ((string) ($response['ResponseCode'] ?? '') !== '0') {
            throw new RuntimeException(
                'M-PESA status check failed: '.($response['ResponseDescription'] ?? $response['errorMessage'] ?? 'Safaricom did not accept the status check.')
            );
        }
    }

    private function paypalBaseUrl(string $mode): string
    {
        return $mode === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
    }

    public function paypalSupportsCurrency(string $currency): bool
    {
        return in_array(strtoupper($currency), [
            'AUD', 'BRL', 'CAD', 'CNY', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'ILS',
            'JPY', 'MYR', 'MXN', 'TWD', 'NZD', 'NOK', 'PHP', 'PLN', 'GBP', 'SGD',
            'SEK', 'CHF', 'THB', 'USD',
        ], true);
    }

    public function paypalCheckoutAmount(SubscriptionInvoice $invoice, array $config = []): array
    {
        $currency = strtoupper($invoice->currency);
        $invoiceAmount = (float) $invoice->total;

        if ($currency === 'KES') {
            $rate = (float) ($config['kes_usd_rate'] ?? 0);
            $exchangeRateSource = $rate > 0 ? 'owner_config' : null;
            $exchangeRateDate = null;

            try {
                $quote = $this->exchangeRates->usdToKes();
                $liveRate = (float) ($quote['rate'] ?? 0);

                if ($liveRate > 0) {
                    $rate = $liveRate;
                    $exchangeRateSource = 'live';
                    $exchangeRateDate = $quote['date'] ?? null;
                }
            } catch (RuntimeException) {
                // Keep the owner-configured rate as a fallback when the live source is unreachable.
            }

            if ($rate <= 0) {
                throw new RuntimeException('PayPal KES to USD exchange rate is not configured in the owner console.');
            }

            $amount = [
                'currency' => 'USD',
                'value' => number_format(max(0.01, $invoiceAmount / $rate), 2, '.', ''),
                'exchange_rate' => $rate,
                'exchange_rate_source' => $exchangeRateSource,
                'source_currency' => 'KES',
                'source_value' => number_format($invoiceAmount, 2, '.', ''),
            ];

            if ($exchangeRateDate) {
                $amount['exchange_rate_date'] = $exchangeRateDate;
            }

            return $amount;
        }

        if (! $this->paypalSupportsCurrency($currency)) {
            throw new RuntimeException("PayPal checkout is not available for {$currency} invoices. Use M-PESA or configure a card checkout for local currency payments.");
        }

        return [
            'currency' => $currency,
            'value' => number_format($invoiceAmount, 2, '.', ''),
        ];
    }

    private function normalizeMpesaPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($digits, '2540')) {
            $digits = '254'.substr($digits, 4);
        } elseif (str_starts_with($digits, '0')) {
            $digits = '254'.substr($digits, 1);
        } elseif (str_starts_with($digits, '7') || str_starts_with($digits, '1')) {
            $digits = '254'.$digits;
        }

        if (! preg_match('/^254[17]\d{8}$/', $digits)) {
            throw new RuntimeException('Enter a valid M-PESA phone number: 10 digits like 0700000000 or 12 digits like 254700000000.');
        }

        return $digits;
    }

    private function failPaymentFromException(SubscriptionPayment $payment, RequestException $exception, string $stage): void
    {
        $response = $exception->response;
        $payload = $response->json() ?? [];

        $this->payments->transition($payment, PaymentStatus::Failed, [
            'failure_code' => $payload['errorCode'] ?? $payload['name'] ?? (string) $response->status(),
            'failure_message' => $payload['errorMessage'] ?? $payload['message'] ?? $payload['ResponseDescription'] ?? "{$stage} failed.",
            'response_payload' => $this->audit->sanitize($payload ?: ['body' => $response->body()]),
        ]);
    }

    private function mpesaRequestException(RequestException $exception, string $stage): RuntimeException
    {
        $response = $exception->response;
        $payload = $response->json() ?? [];

        $this->audit->record(null, 'mpesa_request_failed', [
            'stage' => $stage,
            'status' => $response->status(),
            'error_code' => $payload['errorCode'] ?? $payload['ResponseCode'] ?? null,
            'request_id' => $payload['requestId'] ?? null,
            'response' => $payload ?: $response->body(),
        ]);

        $message = $payload['errorMessage']
            ?? $payload['ResponseDescription']
            ?? $payload['ResultDesc']
            ?? 'Safaricom rejected the M-PESA request. Check the shortcode, passkey, app environment, callback URL, and phone number.';

        return new RuntimeException('M-PESA request failed: '.$message, previous: $exception);
    }

    private function paypalRequestException(RequestException $exception, string $stage): RuntimeException
    {
        $response = $exception->response;
        $payload = $response->json() ?? [];
        $detail = collect($payload['details'] ?? [])->first();

        $this->audit->record(null, 'paypal_request_failed', [
            'stage' => $stage,
            'status' => $response->status(),
            'name' => $payload['name'] ?? null,
            'issue' => $detail['issue'] ?? null,
            'response' => $payload ?: $response->body(),
        ]);

        $message = $detail['description']
            ?? $payload['message']
            ?? $payload['name']
            ?? 'PayPal rejected the checkout request.';

        return new RuntimeException('PayPal request failed: '.$message, previous: $exception);
    }

    private function safeMpesaPayload(array $payload, array $mpesa): array
    {
        $safe = $payload;
        $safe['Password'] = '[redacted]';
        $safe['diagnostics'] = [
            'environment' => $mpesa['mode'],
            'callback_host' => parse_url($mpesa['callback_url'], PHP_URL_HOST),
        ];

        return $safe;
    }

    private function mpesaDiagnostics(array $mpesa, int $amount, string $phone, string $accountReference, string $timestamp): array
    {
        return [
            'mode' => $mpesa['mode'],
            'shortcode' => $mpesa['shortcode'],
            'transaction_type' => $mpesa['transaction_type'],
            'amount' => $amount,
            'phone' => $phone,
            'masked_phone' => substr($phone, 0, 4).'****'.substr($phone, -3),
            'callback_url' => $mpesa['callback_url'],
            'callback_host' => parse_url($mpesa['callback_url'], PHP_URL_HOST),
            'account_reference' => $accountReference,
            'timestamp' => $timestamp,
        ];
    }

    private function merchantReference(SubscriptionInvoice $invoice): string
    {
        do {
            $reference = 'PAY-'.now()->format('Ymd').'-'.$invoice->tenant_id.'-'.Str::upper(Str::random(6));
        } while (SubscriptionPayment::where('merchant_reference', $reference)->exists());

        return $reference;
    }

    private function storeWebhookEvent(string $gateway, ?string $eventId, ?string $eventType, array $payload, bool $signatureValid): ?PaymentWebhookEvent
    {
        if (! Schema::hasTable('payment_webhook_events')) {
            return null;
        }

        $key = $eventId ?: sha1($gateway.'|'.$eventType.'|'.json_encode($payload));

        return PaymentWebhookEvent::firstOrCreate(
            ['gateway' => $gateway, 'event_id' => $key],
            [
                'event_type' => $eventType,
                'payload_json' => $this->audit->sanitize($payload),
                'signature_valid' => $signatureValid,
                'processed' => false,
                'received_at' => now(),
            ]
        );
    }

    private function verifyPayPalWebhook(Request $request, array $payload): bool
    {
        $setting = PlatformPaymentSetting::where('provider', 'paypal')->first();
        $webhookId = data_get($setting?->config ?? [], 'webhook_id') ?: config('services.paypal.webhook_id');

        if (! $webhookId) {
            return false;
        }

        $clientId = $setting?->public_key ?: config('services.paypal.client_id');
        $secret = $setting?->secret_key ?: config('services.paypal.secret');
        $baseUrl = $this->paypalBaseUrl($setting?->mode ?? config('services.paypal.mode', 'sandbox'));

        if (! $clientId || ! $secret) {
            return false;
        }

        try {
            $token = $this->paypalAccessToken($baseUrl, $clientId, $secret, 'webhook_authorization');
            $verification = Http::withToken($token)
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->post($baseUrl.'/v1/notifications/verify-webhook-signature', [
                    'auth_algo' => $request->header('PAYPAL-AUTH-ALGO'),
                    'cert_url' => $request->header('PAYPAL-CERT-URL'),
                    'transmission_id' => $request->header('PAYPAL-TRANSMISSION-ID'),
                    'transmission_sig' => $request->header('PAYPAL-TRANSMISSION-SIG'),
                    'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME'),
                    'webhook_id' => $webhookId,
                    'webhook_event' => $payload,
                ])
                ->throw()
                ->json();
        } catch (\Throwable) {
            return false;
        }

        return ($verification['verification_status'] ?? null) === 'SUCCESS';
    }

    private function verifyStripeWebhook(Request $request): bool
    {
        $setting = PlatformPaymentSetting::where('provider', 'card')->first();
        $secret = data_get($setting?->config ?? [], 'webhook_secret') ?: config('services.stripe.webhook_secret');
        $signature = $request->header('Stripe-Signature');

        if (! $secret || ! $signature) {
            return false;
        }

        $parts = collect(explode(',', $signature))
            ->mapWithKeys(function ($part) {
                [$key, $value] = array_pad(explode('=', $part, 2), 2, null);

                return [$key => $value];
            });
        $timestamp = $parts->get('t');
        $given = $parts->get('v1');

        if (! $timestamp || ! $given || abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);

        return hash_equals($expected, $given);
    }
}
