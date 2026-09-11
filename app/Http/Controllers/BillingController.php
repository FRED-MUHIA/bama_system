<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\PlatformPaymentSetting;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Services\Billing\PaymentGatewayService;
use App\Services\Billing\SubscriptionBillingService;
use App\Services\SubscriptionManager;
use App\Support\ActiveTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class BillingController extends Controller
{
    public function index(SubscriptionBillingService $billing, SubscriptionManager $subscriptions)
    {
        $tenant = ActiveTenant::current();
        abort_unless($tenant, 403, 'No organisation is assigned to this login.');

        $invoice = $tenant->subscription && Schema::hasTable('subscription_invoices')
            ? $billing->currentInvoiceFor($tenant)
            : null;

        return view('billing.index', [
            'tenant' => $tenant->loadMissing('subscription.plan'),
            'plans' => Plan::where('is_active', true)->orderBy('monthly_price')->get(),
            'invoice' => $invoice?->loadMissing(['plan', 'payments' => fn ($query) => $query->latest()]),
            'invoices' => Schema::hasTable('subscription_invoices')
                ? SubscriptionInvoice::with('plan')->where('tenant_id', $tenant->id)->latest()->limit(10)->get()
                : collect(),
            'paymentSettings' => Schema::hasTable('platform_payment_settings')
                ? PlatformPaymentSetting::all()->keyBy('provider')
                : collect(),
            'billingState' => $subscriptions->billingState($tenant),
        ]);
    }

    public function invoice(Request $request, SubscriptionBillingService $billing)
    {
        $tenant = ActiveTenant::current();
        abort_unless($tenant?->subscription, 403, 'No subscription is assigned to this profile.');

        $data = $request->validate([
            'plan_id' => ['required', Rule::exists('plans', 'id')],
        ]);

        $plan = Plan::where('is_active', true)->findOrFail($data['plan_id']);
        abort_if((float) $plan->monthly_price <= 0, 422, 'Custom packages need sales approval before checkout.');

        $invoice = $billing->createInvoice($tenant->subscription, $plan);
        $sent = $billing->sendInvoice($invoice);

        return redirect()->route('billing.index')->with(
            'status',
            'Bama invoice '.$invoice->invoice_number.' is ready for payment.'
                .($sent > 0 ? ' It was emailed to the billing profile email.' : ' Add a profile email to receive invoices automatically.')
        );
    }

    public function mpesa(Request $request, SubscriptionInvoice $invoice, PaymentGatewayService $gateway)
    {
        $this->authorizeInvoice($invoice);

        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30', 'regex:/^(?:\+?254|0)?[\s-]*[17]\d{2}[\s-]*\d{3}[\s-]*\d{3}$/'],
        ], [
            'phone.regex' => 'Enter a valid Safaricom M-PESA number: 0700000000, 254700000000, or +254 700 000 000.',
        ]);

        try {
            $payment = $gateway->mpesaStkPush($invoice, $data['phone']);
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['mpesa' => $this->gatewayError($e, 'M-PESA')])->withInput();
        }

        $mode = data_get($payment->callback_payload, 'normalized_request.mode', 'sandbox');
        $phone = $payment->phone;

        if ($mode === 'sandbox') {
            return back()->with(
                'warning',
                'M-PESA is in sandbox mode. Safaricom accepted a test STK request, but no real phone prompt will appear. Switch M-PESA to Live in the owner payment settings and use live Daraja credentials to prompt '.$phone.'. Reference: '.$payment->checkout_request_id
            );
        }

        return back()->with(
            'status',
            'Safaricom accepted the M-PESA request for '.$phone.', but handset delivery is not confirmed yet. If no prompt appears within 30 seconds, use Check Payment Status for the reason. Reference: '.$payment->checkout_request_id
        );
    }

    public function mpesaStatus(Request $request, SubscriptionPayment $payment, PaymentGatewayService $gateway)
    {
        $this->authorizePayment($payment);

        try {
            $payment = $gateway->queryMpesaStatus($payment);
        } catch (Throwable $e) {
            report($e);
            $message = $this->gatewayError($e, 'M-PESA');

            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $message, 'final' => false], 422);
            }

            return back()->withErrors(['mpesa' => $message]);
        }

        $result = data_get($payment->callback_payload, 'stk_query.ResultDesc')
            ?? data_get($payment->callback_payload, 'callback.Body.stkCallback.ResultDesc')
            ?? data_get($payment->callback_payload, 'ResponseDescription')
            ?? 'Safaricom has not returned a final result yet.';
        $result = $this->mpesaResultMessage($result);

        $message = match (true) {
            $payment->isSuccessful() => 'M-PESA payment confirmed and subscription renewed.',
            $payment->status === 'failed' => 'M-PESA STK failed: '.$result,
            $payment->status === 'cancelled' => $result,
            default => 'M-PESA status: '.$result,
        };

        if ($request->expectsJson()) {
            return response()->json([
                'status' => $payment->status,
                'message' => $message,
                'final' => in_array($payment->status, ['successful', 'failed', 'cancelled', 'expired'], true),
            ]);
        }

        if ($payment->isSuccessful()) {
            return back()->with('status', $message);
        }

        if (in_array($payment->status, ['failed', 'cancelled', 'expired'], true)) {
            return back()->withErrors(['mpesa' => $message]);
        }

        return back()->with('status', $message);
    }

    public function mpesaRedirect()
    {
        return redirect()
            ->route('billing.index')
            ->withErrors(['mpesa' => 'Use the Prompt Phone button to start an M-PESA STK request.']);
    }

    public function mpesaCallback(Request $request, PaymentGatewayService $gateway)
    {
        $payment = $gateway->handleMpesaCallback($request->all());

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => $payment ? 'Accepted' : 'No matching Bama payment',
        ]);
    }

    public function paypal(SubscriptionInvoice $invoice, PaymentGatewayService $gateway)
    {
        $this->authorizeInvoice($invoice);

        try {
            $payment = $gateway->createPayPalOrder($invoice);
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['paypal' => $this->gatewayError($e, 'PayPal')]);
        }

        if (! $payment->payment_url) {
            return back()->withErrors(['paypal' => 'PayPal did not return an approval link.']);
        }

        return redirect()->away($payment->payment_url);
    }

    public function paypalReturn(Request $request, PaymentGatewayService $gateway)
    {
        $orderId = $request->query('token');
        abort_unless($orderId, 422, 'PayPal order token is missing.');

        try {
            $payment = $gateway->capturePayPalOrder($orderId);
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('billing.index')->withErrors(['paypal' => $this->gatewayError($e, 'PayPal')]);
        }

        if (! $payment->isSuccessful()) {
            return redirect()->route('billing.index')->with(
                'warning',
                'PayPal received the payment request, but settlement is still processing. The subscription will activate after PayPal confirms the capture.'
            );
        }

        return redirect()->route('billing.index')->with('status', 'PayPal payment verified and subscription renewed. Reference: '.$payment->provider_receipt.'.');
    }

    public function paypalCancel(Request $request, PaymentGatewayService $gateway)
    {
        $orderId = $request->query('token');
        $payment = $orderId
            ? SubscriptionPayment::where('provider', 'paypal')->where('provider_order_id', $orderId)->latest()->first()
            : null;

        if ($payment && ActiveTenant::id() && (int) $payment->tenant_id === (int) ActiveTenant::id()
            && in_array($payment->status, ['created', 'requires_action', 'processing'], true)) {
            $gateway->cancelPayPalCheckout($payment);
        }

        return redirect()->route('billing.index')->with('warning', 'PayPal payment was cancelled.');
    }

    public function card(SubscriptionInvoice $invoice, PaymentGatewayService $gateway)
    {
        $this->authorizeInvoice($invoice);

        try {
            $payment = $gateway->cardCheckout($invoice);
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['card' => $this->gatewayError($e, 'Card')]);
        }

        return redirect()->route('billing.payments.card-confirm', $payment);
    }

    public function cardConfirm(SubscriptionPayment $payment)
    {
        abort_unless(ActiveTenant::id() && (int) $payment->tenant_id === (int) ActiveTenant::id(), 403);
        abort_unless($payment->provider === 'card', 404);
        $payment->loadMissing('invoice.plan');

        $setting = PlatformPaymentSetting::where('provider', 'card')->first();
        $publicKey = $setting?->public_key ?: config('services.stripe.key');
        abort_unless($publicKey, 422, 'Card payments are not fully configured.');

        return view('billing.card', [
            'payment' => $payment,
            'invoice' => $payment->invoice,
            'stripeKey' => $publicKey,
            'clientSecret' => data_get($payment->response_payload, 'client_secret'),
            'returnUrl' => URL::signedRoute('billing.index'),
        ]);
    }

    public function paypalWebhook(Request $request, PaymentGatewayService $gateway)
    {
        $payment = $gateway->handlePayPalWebhook($request);

        return response()->json([
            'received' => true,
            'processed' => (bool) $payment,
        ]);
    }

    public function stripeWebhook(Request $request, PaymentGatewayService $gateway)
    {
        $payment = $gateway->handleStripeWebhook($request);

        return response()->json([
            'received' => true,
            'processed' => (bool) $payment,
        ]);
    }

    private function authorizeInvoice(SubscriptionInvoice $invoice): void
    {
        abort_unless(ActiveTenant::id() && (int) $invoice->tenant_id === (int) ActiveTenant::id(), 403);
        abort_if($invoice->status === 'paid', 422, 'This Bama invoice is already paid.');
        abort_if((float) $invoice->total <= 0, 422, 'This package needs sales approval before checkout.');
    }

    private function authorizePayment(SubscriptionPayment $payment): void
    {
        abort_unless(ActiveTenant::id() && (int) $payment->tenant_id === (int) ActiveTenant::id(), 403);
        abort_unless($payment->provider === 'mpesa', 404);
    }

    private function mpesaResultMessage(string $result): string
    {
        $lower = strtolower($result);

        return match (true) {
            str_contains($lower, 'wrong credentials') || str_contains($lower, 'initiator information is invalid') || str_contains($lower, 'invalid credentials') => 'M-PESA could not authenticate this STK request. If no phone prompt appeared, check the Live shortcode, passkey, transaction type, and Daraja app environment. If a prompt appeared, send a new prompt and enter the correct M-PESA PIN.',
            str_contains($lower, 'invalid phone') || str_contains($lower, 'invalid phonenumber') => 'Enter a valid Safaricom M-PESA number, for example 0700000000 or +254 700 000 000.',
            str_contains($lower, 'unable to lock subscriber') || str_contains($lower, 'transaction is already in process') => 'That phone already has an M-PESA request in progress. Wait a moment, complete or cancel it, then send a new prompt.',
            str_contains($lower, 'timeout') || str_contains($lower, 'cannot be reached') => 'The phone could not be reached or the STK prompt timed out. Confirm the phone has signal, then send a new prompt.',
            str_contains($lower, 'cancel') => 'The payer cancelled the M-PESA prompt. Send a new prompt to try again.',
            default => $result,
        };
    }

    private function gatewayError(Throwable $exception, string $provider): string
    {
        if ($exception instanceof RuntimeException) {
            return $exception->getMessage();
        }

        return $provider.' payments are temporarily unavailable. The error has been logged; please try again or contact support.';
    }
}
