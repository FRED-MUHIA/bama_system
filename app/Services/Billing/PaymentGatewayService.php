<?php

namespace App\Services\Billing;

use App\Models\PlatformPaymentSetting;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class PaymentGatewayService
{
    public function mpesaStkPush(SubscriptionInvoice $invoice, string $phone): SubscriptionPayment
    {
        $setting = $this->setting('mpesa');
        $config = $setting->config ?? [];
        $consumerKey = $setting->public_key ?: config('services.mpesa.consumer_key');
        $consumerSecret = $setting->secret_key ?: config('services.mpesa.consumer_secret');
        $shortcode = $config['shortcode'] ?? config('services.mpesa.shortcode');
        $passkey = $config['passkey'] ?? config('services.mpesa.passkey');
        $callbackUrl = $config['callback_url'] ?? route('billing.mpesa.callback');

        if (! $consumerKey || ! $consumerSecret || ! $shortcode || ! $passkey) {
            throw new RuntimeException('M-PESA STK is not fully configured in the owner console.');
        }

        $baseUrl = $this->mpesaBaseUrl($setting->mode);
        $token = Http::withBasicAuth($consumerKey, $consumerSecret)
            ->timeout(30)
            ->get($baseUrl.'/oauth/v1/generate', ['grant_type' => 'client_credentials'])
            ->throw()
            ->json('access_token');

        $timestamp = now()->format('YmdHis');
        $normalizedPhone = $this->normalizeMpesaPhone($phone);
        $amount = max(1, (int) round((float) $invoice->total));

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(30)
            ->post($baseUrl.'/mpesa/stkpush/v1/processrequest', [
                'BusinessShortCode' => $shortcode,
                'Password' => base64_encode($shortcode.$passkey.$timestamp),
                'Timestamp' => $timestamp,
                'TransactionType' => $config['transaction_type'] ?? 'CustomerPayBillOnline',
                'Amount' => $amount,
                'PartyA' => $normalizedPhone,
                'PartyB' => $shortcode,
                'PhoneNumber' => $normalizedPhone,
                'CallBackURL' => $callbackUrl,
                'AccountReference' => Str::limit($invoice->invoice_number, 12, ''),
                'TransactionDesc' => 'BAMA '.$invoice->invoice_number,
            ])
            ->throw()
            ->json();

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

    public function createPayPalOrder(SubscriptionInvoice $invoice): SubscriptionPayment
    {
        $setting = $this->setting('paypal');
        $clientId = $setting->public_key ?: config('services.paypal.client_id');
        $secret = $setting->secret_key ?: config('services.paypal.secret');

        if (! $clientId || ! $secret) {
            throw new RuntimeException('PayPal is not fully configured in the owner console.');
        }

        $baseUrl = $this->paypalBaseUrl($setting->mode);
        $token = Http::asForm()
            ->withBasicAuth($clientId, $secret)
            ->timeout(30)
            ->post($baseUrl.'/v1/oauth2/token', ['grant_type' => 'client_credentials'])
            ->throw()
            ->json('access_token');

        $order = Http::withToken($token)
            ->acceptJson()
            ->timeout(30)
            ->post($baseUrl.'/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $invoice->invoice_number,
                    'description' => 'BAMA '.$invoice->plan?->name.' subscription',
                    'amount' => [
                        'currency_code' => strtoupper($invoice->currency),
                        'value' => number_format((float) $invoice->total, 2, '.', ''),
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

        $approvalUrl = collect($order['links'] ?? [])->firstWhere('rel', 'approve')['href'] ?? null;

        return $invoice->payments()->create([
            'tenant_id' => $invoice->tenant_id,
            'provider' => 'paypal',
            'status' => 'requires_action',
            'amount' => $invoice->total,
            'currency' => $invoice->currency,
            'provider_order_id' => $order['id'] ?? null,
            'payment_url' => $approvalUrl,
            'callback_payload' => $order,
        ]);
    }

    public function capturePayPalOrder(string $orderId): SubscriptionPayment
    {
        $payment = SubscriptionPayment::where('provider', 'paypal')->where('provider_order_id', $orderId)->latest()->firstOrFail();
        $setting = $this->setting('paypal');
        $clientId = $setting->public_key ?: config('services.paypal.client_id');
        $secret = $setting->secret_key ?: config('services.paypal.secret');
        $baseUrl = $this->paypalBaseUrl($setting->mode);

        $token = Http::asForm()
            ->withBasicAuth($clientId, $secret)
            ->timeout(30)
            ->post($baseUrl.'/v1/oauth2/token', ['grant_type' => 'client_credentials'])
            ->throw()
            ->json('access_token');

        $capture = Http::withToken($token)
            ->acceptJson()
            ->timeout(30)
            ->post($baseUrl.'/v2/checkout/orders/'.$orderId.'/capture')
            ->throw()
            ->json();

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

    private function normalizeMpesaPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($digits, '0')) {
            return '254'.substr($digits, 1);
        }

        if (str_starts_with($digits, '7') || str_starts_with($digits, '1')) {
            return '254'.$digits;
        }

        return $digits;
    }
}
