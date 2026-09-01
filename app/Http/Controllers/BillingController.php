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
            'phone' => ['required', 'string', 'max:30'],
        ]);

        try {
            $payment = $gateway->mpesaStkPush($invoice, $data['phone']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['mpesa' => $e->getMessage()])->withInput();
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
            'M-PESA payment request initiated for '.$phone.'. Complete the request on that phone, or use Check Payment Status if no prompt appears. Reference: '.$payment->checkout_request_id
        );
    }

    public function mpesaStatus(SubscriptionPayment $payment, PaymentGatewayService $gateway)
    {
        $this->authorizePayment($payment);

        try {
            $payment = $gateway->queryMpesaStatus($payment);
        } catch (RuntimeException $e) {
            return back()->withErrors(['mpesa' => $e->getMessage()]);
        }

        $result = data_get($payment->callback_payload, 'stk_query.ResultDesc')
            ?? data_get($payment->callback_payload, 'Body.stkCallback.ResultDesc')
            ?? data_get($payment->callback_payload, 'ResponseDescription')
            ?? 'Safaricom has not returned a final result yet.';
        $result = $this->mpesaResultMessage($result);

        if ($payment->isSuccessful()) {
            return back()->with('status', 'M-PESA payment confirmed and subscription renewed.');
        }

        if ($payment->status === 'failed') {
            return back()->withErrors(['mpesa' => 'M-PESA STK failed: '.$result]);
        }

        return back()->with('status', 'M-PESA STK status: '.$result);
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
        } catch (RuntimeException $e) {
            return back()->withErrors(['paypal' => $e->getMessage()]);
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
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('billing.index')->withErrors(['paypal' => 'PayPal capture failed: '.$e->getMessage()]);
        }

        return redirect()->route('billing.index')->with('status', 'PayPal payment verified and subscription renewed. Reference: '.$payment->provider_receipt.'.');
    }

    public function paypalCancel()
    {
        return redirect()->route('billing.index')->with('warning', 'PayPal payment was cancelled.');
    }

    public function card(SubscriptionInvoice $invoice, PaymentGatewayService $gateway)
    {
        $this->authorizeInvoice($invoice);

        try {
            $payment = $gateway->cardCheckout($invoice);
        } catch (RuntimeException $e) {
            return back()->withErrors(['card' => $e->getMessage()]);
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
        return match (true) {
            str_contains(strtolower($result), 'wrong credentials') => 'The payer entered the wrong M-PESA PIN or could not be authenticated. Send a new prompt and enter the correct PIN.',
            str_contains(strtolower($result), 'timeout') || str_contains(strtolower($result), 'cannot be reached') => 'The phone could not be reached or the STK prompt timed out. Confirm the phone has signal, then send a new prompt.',
            str_contains(strtolower($result), 'cancel') => 'The payer cancelled the M-PESA prompt. Send a new prompt to try again.',
            default => $result,
        };
    }
}
