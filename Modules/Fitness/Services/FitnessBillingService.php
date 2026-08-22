<?php

namespace Modules\Fitness\Services;

use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Receipt;
use App\Models\ReceiptAllocation;
use App\Services\DocumentService;
use App\Services\IamService;
use App\Services\InvoiceVerificationService;
use App\Services\OutgoingMailService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Fitness\Models\MemberMembership;

class FitnessBillingService
{
    public function __construct(
        private DocumentService $documents,
        private InvoiceVerificationService $verification,
        private OutgoingMailService $outgoingMail,
    ) {}

    public function invoiceMembership(MemberMembership $membership): Invoice
    {
        return DB::transaction(function () use ($membership) {
            $membership->load('member.client', 'plan');

            if ($membership->invoice_id) {
                return $membership->invoice()->with('items')->firstOrFail();
            }

            $amount = round((float) $membership->price_charged + (float) $membership->joining_fee_charged, 2);
            $invoice = Invoice::create([
                'business_id' => $membership->business_id,
                'client_id' => $membership->member->client_id,
                'invoice_number' => $this->documents->number('invoice'),
                'public_token' => Str::random(48),
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(7)->toDateString(),
                'payment_status' => $amount > 0 ? 'unpaid' : 'paid',
                'subtotal' => $amount,
                'discount_total' => 0,
                'tax_total' => 0,
                'total' => $amount,
                'amount_paid' => 0,
                'balance' => $amount,
                'notes' => 'Fitness membership '.$membership->membership_number,
            ]);

            $invoice->items()->create([
                'title' => 'Fitness Membership',
                'description' => $membership->plan->name.' - '.$membership->membership_number,
                'quantity' => 1,
                'unit_price' => $amount,
                'discount' => 0,
                'tax_rate' => 0,
                'line_total' => $amount,
            ]);

            $membership->update(['invoice_id' => $invoice->id, 'balance' => $amount]);

            return $invoice->load('items');
        });
    }

    public function recordMembershipPayment(MemberMembership $membership, array $data): Receipt
    {
        return DB::transaction(function () use ($membership, $data) {
            $membership = MemberMembership::with('invoice', 'member.client', 'plan')->lockForUpdate()->findOrFail($membership->id);
            $invoice = $membership->invoice_id ? $membership->invoice : $this->invoiceMembership($membership);
            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);

            $amount = round((float) $data['amount'], 2);
            if ($amount <= 0 || $amount > (float) $invoice->balance) {
                throw ValidationException::withMessages(['amount' => 'Payment amount exceeds the outstanding membership balance.']);
            }

            $payment = $invoice->payments()->create($data + [
                'payable_type' => $membership->getMorphClass(),
                'payable_id' => $membership->id,
            ]);

            $paid = (float) $invoice->payments()->sum('amount');
            $balance = max((float) $invoice->total - $paid, 0);
            $invoice->update([
                'amount_paid' => $paid,
                'balance' => $balance,
                'payment_status' => $balance <= 0 ? 'paid' : 'partial',
            ]);

            $membership->update(['balance' => $balance, 'status' => $membership->status === 'Pending' && $balance <= 0 ? 'Active' : $membership->status]);
            if ($balance <= 0) {
                $membership->member()->update(['status' => 'Active']);
            }

            $receipt = $invoice->receipts()->create([
                'payment_id' => $payment->id,
                'receipt_number' => $this->documents->number('receipt'),
                'amount_paid' => $payment->amount,
                'balance_remaining' => $balance,
                'status' => $balance <= 0 ? 'Paid' : 'Partial',
                'payment_method' => $payment->paymentMethod?->name,
                'payment_date' => $payment->payment_date,
            ]);

            if (Schema::hasTable('receipt_allocations')) {
                ReceiptAllocation::create([
                    'business_id' => $invoice->business_id,
                    'receipt_id' => $receipt->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $payment->amount,
                ]);
            }

            $membership->events()->create([
                'business_id' => $membership->business_id,
                'user_id' => auth()->id(),
                'event' => 'membership.payment.recorded',
                'new_values' => ['payment_id' => $payment->id, 'receipt_id' => $receipt->id, 'amount' => $payment->amount],
            ]);

            app(IamService::class)->audit('membership.payment.recorded', $payment);

            return $receipt;
        });
    }

    public function sendInvoiceAfterPayment(Invoice $invoice, Receipt $receipt): bool
    {
        $invoice->load('client', 'items', 'payments.paymentMethod', 'receipts');
        $email = $invoice->client?->email;
        $subject = 'Payment received - invoice '.$invoice->invoice_number;
        $message = "Hello {$invoice->client?->name},\n\n"
            .'Thank you. We have received your payment of '.number_format((float) $receipt->amount_paid, 2)
            ." for invoice {$invoice->invoice_number}.\n\n"
            ."The updated invoice is attached for your records.\n\n"
            .'Thank you.';

        if (! $email) {
            $invoice->emailLogs()->create([
                'recipient_email' => '',
                'subject' => $subject,
                'message' => $message,
                'status' => 'failed',
                'error' => 'Client does not have an email address.',
            ]);

            return false;
        }

        try {
            $this->outgoingMail->sendRaw(
                $email,
                $subject,
                $message,
                fn ($mail) => $mail->attachData($this->invoicePdf($invoice)->output(), $invoice->invoice_number.'.pdf', ['mime' => 'application/pdf']),
                $invoice->business_id,
            );

            $invoice->emailLogs()->create([
                'recipient_email' => $email,
                'subject' => $subject,
                'message' => $message,
                'status' => 'sent',
                'sent_at' => now(),
            ]);
            $invoice->update(['sent_at' => now()]);

            return true;
        } catch (\Throwable $e) {
            report($e);
            $invoice->emailLogs()->create([
                'recipient_email' => $email,
                'subject' => $subject,
                'message' => $message,
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function invoicePdf(Invoice $invoice)
    {
        $invoice->load('items');
        $invoice->setRelation('client', Client::withoutGlobalScope('business')->find($invoice->client_id) ?: $invoice->client);

        return Pdf::loadView('pdf.document', [
            'type' => 'Invoice',
            'document' => $invoice,
            'settings' => CompanySetting::withoutGlobalScope('business')->where('business_id', $invoice->business_id)->first() ?: CompanySetting::first(),
            'paymentMethods' => PaymentMethod::withoutGlobalScope('business')->where('business_id', $invoice->business_id)->where('is_active', true)->get(),
            'verificationUrl' => $this->verification->url($invoice),
            'qrCode' => $this->verification->qrCodeDataUri($invoice, 150),
        ]);
    }
}
