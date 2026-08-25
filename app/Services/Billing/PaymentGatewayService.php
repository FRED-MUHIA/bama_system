<?php

namespace App\Services\Billing;

use App\Models\PlatformPaymentSetting;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class PaymentGatewayService
{
    public function mpesaStkPush(SubscriptionInvoice $invoice, string $phone): SubscriptionPayment
    {
        $setting = $this->setting('mpesa');
        $config = $setting->config ?? [];
        $consumerKey = trim((string) ($setting->public_key ?: config('services.mpesa.consumer_key')));
        $consumerSecret = trim((string) ($setting->secret_key ?: config('services.mpesa.consumer_secret')));
        $shortcode = trim((string) ($config['shortcode'] ?? config('services.mpesa.shortcode')));
        $passkey = trim((string) ($config['passkey'] ?? config('services.mpesa.passkey')));
        $callbackUrl = trim((string) ($config['callback_url'] ?? route('billing.mpesa.callback')));
        $transactionType = trim((string) ($config['transaction_type'] ?? 'CustomerPayBillOnline'));

        if (! $consumerKey || ! $consumerSecret || ! $shortcode || ! $passkey) {
            throw new RuntimeException('M-PESA STK is not fully configured in the owner console.');
        }

        if (! in_array($transactionType, ['CustomerPayBillOnline', 'CustomerBuyGoodsOnline'], true)) {
            throw new RuntimeException('Choose a valid M-PESA transaction type: PayBill or Buy Goods.');
        }

        $timestamp = now()->format('YmdHis');
        $normalizedPhone = $this->normalizeMpesaPhone($phone);
        $amount = max(1, (int) round((float) $invoice->total));
        $accountReference = Str::limit($invoice->invoice_number, 12, '');

        $baseUrl = $this->mpesaBaseUrl($setting->mode);
        try {
            $token = Http::withBasicAuth($consumerKey, $consumerSecret)
                ->timeout(30)
                ->get($baseUrl.'/oauth/v1/generate', ['grant_type' => 'client_credentials'])
                ->throw()
                ->json('access_token');
        } catch (RequestException $e) {
            throw $this->mpesaRequestException($e, 'authorization');
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(30)
                ->post($baseUrl.'/mpesa/stkpush/v1/processrequest', [
                    'BusinessShortCode' => $shortcode,
                    'Password' => base64_encode($shortcode.$passkey.$timestamp),
                    'Timestamp' => $timestamp,
                    'TransactionType' => $transactionType,
                    'Amount' => $amount,
                    'PartyA' => $normalizedPhone,
                    'PartyB' => $shortcode,
                    'PhoneNumber' => $normalizedPhone,
                    'CallBackURL' => $callbackUrl,
                    'AccountReference' => $accountReference,
                    'TransactionDesc' => 'BAMA Invoice',
                ])
                ->throw()
                ->json();
        } catch (RequestException $e) {
            throw $this->mpesaRequestException($e, 'stk_push');
        }

        $responseCode = (string) ($response['ResponseCode'] ?? '0');
        if ($responseCode !== '0') {
            throw new RuntimeException(
                'M-PESA request failed: '.($response['ResponseDescription'] ?? $response['errorMessage'] ?? 'Safaricom did not accept the STK request.')
            );
        }

        if (blank($response['CheckoutRequestID'] ?? null)) {
            throw new RuntimeException('M-PESA request failed: Safaricom did not return a checkout request ID.');
        }

        return $invoice->payments()->create([
            'tenant_id' => $invoice->tenant_id,
            'provider' => 'mpesa',
            'status' => 'pending',
            'amount' => $invoice->total,
            'currency' => $invoice->currency,
            'checkout_request_id' => $response['CheckoutRequestID'] ?? null,
            'merchant_request_id' => $response['MerchantRequestID'] ?? null,
            'phone' => $normalizedPhone,
            'callback_payload' => $response,
        ]);
    }

    public function handleMpesaCallback(array $payload): ?SubscriptionPayment
    {
        $stk = data_get($payload, 'Body.stkCallback', []);
        $checkoutRequestId = $stk['CheckoutRequestID'] ?? null;

        if (! $checkoutRequestId || ! Schema::hasTable('subscription_payments')) {
            return null;
        }

        $payment = SubscriptionPayment::where('checkout_request_id', $checkoutRequestId)->latest()->first();
        if (! $payment) {
            return null;
        }

        $items = collect(data_get($stk, 'CallbackMetadata.Item', []))->pluck('Value', 'Name');
        $resultCode = (int) ($stk['ResultCode'] ?? -1);

        $payment->forceFill([
            'status' => $resultCode === 0 ? 'paid' : 'failed',
            'provider_receipt' => $items['MpesaReceiptNumber'] ?? null,
            'provider_payment_id' => $items['TransactionDate'] ?? null,
            'phone' => $items['PhoneNumber'] ?? $payment->phone,
            'callback_payload' => $payload,
            'paid_at' => $resultCode === 0 ? now() : null,
        ])->save();

        if ($resultCode === 0) {
            app(SubscriptionBillingService::class)->markPaid($payment);
        }

        return $payment->refresh();
    }

    public function queryMpesaStatus(SubscriptionPayment $payment): SubscriptionPayment
    {
        if ($payment->provider !== 'mpesa') {
            throw new RuntimeException('Only M-PESA payments can be checked through Daraja.');
        }

        if (! $payment->checkout_request_id) {
            throw new RuntimeException('This M-PESA payment does not have a checkout request ID.');
        }

        $setting = $this->setting('mpesa');
        $config = $setting->config ?? [];
        $consumerKey = trim((string) ($setting->public_key ?: config('services.mpesa.consumer_key')));
        $consumerSecret = trim((string) ($setting->secret_key ?: config('services.mpesa.consumer_secret')));
        $shortcode = trim((string) ($config['shortcode'] ?? config('services.mpesa.shortcode')));
        $passkey = trim((string) ($config['passkey'] ?? config('services.mpesa.passkey')));

        if (! $consumerKey || ! $consumerSecret || ! $shortcode || ! $passkey) {
            throw new RuntimeException('M-PESA STK is not fully configured in the owner console.');
        }

        $baseUrl = $this->mpesaBaseUrl($setting->mode);
        $timestamp = now()->format('YmdHis');

        try {
            $token = Http::withBasicAuth($consumerKey, $consumerSecret)
                ->timeout(30)
                ->get($baseUrl.'/oauth/v1/generate', ['grant_type' => 'client_credentials'])
                ->throw()
                ->json('access_token');
        } catch (RequestException $e) {
            throw $this->mpesaRequestException($e, 'status_authorization');
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(30)
                ->post($baseUrl.'/mpesa/stkpushquery/v1/query', [
                    'BusinessShortCode' => $shortcode,
                    'Password' => base64_encode($shortcode.$passkey.$timestamp),
                    'Timestamp' => $timestamp,
                    'CheckoutRequestID' => $payment->checkout_request_id,
                ])
                ->throw()
                ->json();
        } catch (RequestException $e) {
            throw $this->mpesaRequestException($e, 'stk_query');
        }

        if ((string) ($response['ResponseCode'] ?? '0') !== '0') {
            throw new RuntimeException(
                'M-PESA status check failed: '.($response['ResponseDescription'] ?? $response['errorMessage'] ?? 'Safaricom did not accept the status check.')
            );
        }

        $payload = $payment->callback_payload ?? [];
        $payload['stk_query'] = $response;
        $resultCode = array_key_exists('ResultCode', $response) ? (int) $response['ResultCode'] : null;

        $updates = ['callback_payload' => $payload];
        if ($resultCode !== null) {
            $updates['status'] = $resultCode === 0 ? 'paid' : 'failed';
            $updates['paid_at'] = $resultCode === 0 ? now() : null;
        }

        $payment->forceFill($updates)->save();

        if ($resultCode === 0) {
            app(SubscriptionBillingService::class)->markPaid($payment);
        }

        return $payment->refresh();
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

        $baseUrl = $this->paypalBaseUrl($setting->mode);
        try {
            $token = Http::asForm()
                ->withBasicAuth($clientId, $secret)
                ->timeout(30)
                ->post($baseUrl.'/v1/oauth2/token', ['grant_type' => 'client_credentials'])
                ->throw()
                ->json('access_token');
        } catch (RequestException $e) {
            throw $this->paypalRequestException($e, 'authorization');
        }

        try {
            $order = Http::withToken($token)
                ->acceptJson()
                ->timeout(30)
                ->post($baseUrl.'/v2/checkout/orders', [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [[
                        'reference_id' => $invoice->invoice_number,
                        'description' => 'BAMA '.$invoice->plan?->name.' subscription',
                        'amount' => [
                            'currency_code' => $paypalAmount['currency'],
                            'value' => $paypalAmount['value'],
                        ],
                    ]],
                    'application_context' => [
                        'brand_name' => 'BAMA Solutions',
                        'user_action' => 'PAY_NOW',
                        'return_url' => route('billing.paypal.return'),
                        'cancel_url' => route('billing.paypal.cancel'),
                    ],
                ])
                ->throw()
                ->json();
        } catch (RequestException $e) {
            throw $this->paypalRequestException($e, 'create_order');
        }

        $approvalUrl = collect($order['links'] ?? [])->firstWhere('rel', 'approve')['href'] ?? null;

        return $invoice->payments()->create([
            'tenant_id' => $invoice->tenant_id,
            'provider' => 'paypal',
            'status' => 'requires_action',
            'amount' => $invoice->total,
            'currency' => $invoice->currency,
            'provider_order_id' => $order['id'] ?? null,
            'payment_url' => $approvalUrl,
            'callback_payload' => [
                'order' => $order,
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
        $setting = $this->setting('paypal');
        $clientId = $setting->public_key ?: config('services.paypal.client_id');
        $secret = $setting->secret_key ?: config('services.paypal.secret');
        $baseUrl = $this->paypalBaseUrl($setting->mode);

        try {
            $token = Http::asForm()
                ->withBasicAuth($clientId, $secret)
                ->timeout(30)
                ->post($baseUrl.'/v1/oauth2/token', ['grant_type' => 'client_credentials'])
                ->throw()
                ->json('access_token');
        } catch (RequestException $e) {
            throw $this->paypalRequestException($e, 'capture_authorization');
        }

        try {
            $capture = Http::withToken($token)
                ->acceptJson()
                ->timeout(30)
                ->post($baseUrl.'/v2/checkout/orders/'.$orderId.'/capture')
                ->throw()
                ->json();
        } catch (RequestException $e) {
            throw $this->paypalRequestException($e, 'capture_order');
        }

        $status = ($capture['status'] ?? null) === 'COMPLETED' ? 'paid' : 'pending';
        $captureId = data_get($capture, 'purchase_units.0.payments.captures.0.id');

        $payment->forceFill([
            'status' => $status,
            'provider_payment_id' => $captureId,
            'provider_receipt' => $captureId,
            'callback_payload' => $capture,
            'paid_at' => $status === 'paid' ? now() : null,
        ])->save();

        if ($status === 'paid') {
            app(SubscriptionBillingService::class)->markPaid($payment);
        }

        return $payment->refresh();
    }

    public function cardCheckout(SubscriptionInvoice $invoice): SubscriptionPayment
    {
        $setting = $this->setting('card');
        $config = $setting->config ?? [];
        $template = $config['checkout_url_template'] ?? null;

        if (! $template) {
            throw new RuntimeException('Card checkout URL template is not configured in the owner console.');
        }

        $url = strtr($template, [
            '{invoice}' => urlencode($invoice->invoice_number),
            '{amount}' => number_format((float) $invoice->total, 2, '.', ''),
            '{currency}' => urlencode($invoice->currency),
            '{tenant}' => urlencode((string) $invoice->tenant_id),
        ]);

        return $invoice->payments()->create([
            'tenant_id' => $invoice->tenant_id,
            'provider' => 'card',
            'status' => 'requires_action',
            'amount' => $invoice->total,
            'currency' => $invoice->currency,
            'payment_url' => $url,
        ]);
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

    private function mpesaBaseUrl(string $mode): string
    {
        return $mode === 'live' ? 'https://api.safaricom.co.ke' : 'https://sandbox.safaricom.co.ke';
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

            if ($rate <= 0) {
                throw new RuntimeException('PayPal KES to USD exchange rate is not configured in the owner console.');
            }

            return [
                'currency' => 'USD',
                'value' => number_format(max(0.01, $invoiceAmount / $rate), 2, '.', ''),
                'exchange_rate' => $rate,
                'source_currency' => 'KES',
                'source_value' => number_format($invoiceAmount, 2, '.', ''),
            ];
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

        if (str_starts_with($digits, '0')) {
            $digits = '254'.substr($digits, 1);
        } elseif (str_starts_with($digits, '7') || str_starts_with($digits, '1')) {
            $digits = '254'.$digits;
        }

        if (! preg_match('/^254\d{9}$/', $digits)) {
            throw new RuntimeException('Enter a valid M-PESA phone number: 10 digits like 0700000000 or 12 digits like 254700000000.');
        }

        return $digits;
    }

    private function mpesaRequestException(RequestException $exception, string $stage): RuntimeException
    {
        $response = $exception->response;
        $payload = $response->json() ?? [];

        Log::warning('M-PESA request failed.', [
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

        Log::warning('PayPal request failed.', [
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
}
