<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\PaymentMethod;
use App\Models\Receipt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ReceiptController extends Controller
{
    public function index() { return view('receipts.index', ['receipts' => Receipt::with('invoice.client')->latest()->paginate(12)]); }
    public function show(Receipt $receipt)
    {
        $relationships = ['invoice.client', 'payment', 'emailLogs'];
        if (\Illuminate\Support\Facades\Schema::hasTable('letters')) {
            $relationships[] = 'letters';
        }

        return view('receipts.show', ['receipt' => $receipt->load($relationships)]);
    }
    public function download(Receipt $receipt) { return $this->pdf($receipt)->download($receipt->receipt_number . '.pdf'); }
    public function emailForm(Receipt $receipt) { return view('documents.email', ['document' => $receipt->load('invoice.client'), 'type' => 'receipt']); }

    public function sendEmail(Request $request, Receipt $receipt)
    {
        $data = $request->validate(['subject' => ['required', 'string'], 'message' => ['required', 'string']]);
        $receipt->load('invoice.client');
        $email = $receipt->invoice->client->email;
        try {
            Mail::raw($data['message'], function ($mail) use ($receipt, $data, $email) {
                $mail->to($email)->subject($data['subject'])
                    ->attachData($this->pdf($receipt)->output(), $receipt->receipt_number . '.pdf', ['mime' => 'application/pdf']);
            });
            $receipt->emailLogs()->create($data + ['recipient_email' => $email, 'status' => 'sent', 'sent_at' => now()]);
            $receipt->update(['sent_at' => now()]);
            return redirect()->route('receipts.show', $receipt)->with('status', 'Receipt emailed.');
        } catch (\Throwable $e) {
            $receipt->emailLogs()->create($data + ['recipient_email' => $email, 'status' => 'failed', 'error' => $e->getMessage()]);
            return back()->withErrors(['email' => 'Email failed: ' . $e->getMessage()]);
        }
    }

    private function pdf(Receipt $receipt)
    {
        $receipt->load('invoice.client');

        return Pdf::loadView('pdf.receipt', [
            'receipt' => $receipt,
            'settings' => CompanySetting::withoutGlobalScope('business')->where('business_id', $receipt->business_id)->first() ?: CompanySetting::first(),
            'paymentMethods' => PaymentMethod::withoutGlobalScope('business')->where('business_id', $receipt->business_id)->where('is_active', true)->get(),
        ]);
    }
}
