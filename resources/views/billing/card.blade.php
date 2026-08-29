@extends('layouts.app')
@section('title', 'Card Payment')

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-7 col-lg-8">
        <div class="card p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <div class="text-muted small fw-bold text-uppercase">Secure card payment</div>
                    <h1 class="h4 mb-1">{{ $invoice->plan?->name ?? 'BAMA package' }}</h1>
                    <div class="text-muted">{{ $invoice->invoice_number }}</div>
                </div>
                <div class="text-end">
                    <div class="h4 mb-1">{{ $invoice->currency }} {{ number_format((float) $invoice->total, 2) }}</div>
                    <span class="badge text-bg-light">{{ str($payment->status)->headline() }}</span>
                </div>
            </div>

            <div id="payment-element" class="border rounded-2 p-3 mb-3"></div>
            <div id="card-message" class="alert d-none mb-3"></div>

            <button id="submit-card-payment" class="btn btn-warning w-100" type="button">
                <i class="bi bi-credit-card-2-front"></i> Pay Securely
            </button>
        </div>
    </div>
</div>

<script src="https://js.stripe.com/v3/"></script>
<script>
const stripe = Stripe(@json($stripeKey));
const elements = stripe.elements({ clientSecret: @json($clientSecret) });
const paymentElement = elements.create('payment');
paymentElement.mount('#payment-element');

const button = document.getElementById('submit-card-payment');
const message = document.getElementById('card-message');

const showMessage = (text, type = 'warning') => {
    message.className = `alert alert-${type}`;
    message.textContent = text;
};

button.addEventListener('click', async () => {
    button.disabled = true;
    button.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';
    showMessage('Additional verification may be required by your bank.', 'info');

    const { error } = await stripe.confirmPayment({
        elements,
        confirmParams: {
            return_url: @json(route('billing.index')),
        },
    });

    if (error) {
        showMessage(error.message || 'The card payment was not completed.', 'danger');
        button.disabled = false;
        button.innerHTML = '<i class="bi bi-credit-card-2-front"></i> Pay Securely';
    }
});
</script>
@endsection
