<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\PaymentMethod;
use App\Models\Receipt;
use App\Models\Signatory;
use App\Services\OutgoingMailService;
use App\Support\ActiveBusiness;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ReceiptController extends Controller
{
    public function __construct(private OutgoingMailService $outgoingMail) {}

    public function index()
    {
        return view('receipts.index', ['receipts' => Receipt::with('invoice.client')->latest()->paginate(12)]);
    }

    public function show(Receipt $receipt)
    {
        $relationships = ['invoice.client', 'payment', 'emailLogs'];
        if (Schema::hasTable('letters')) {
            $relationships[] = 'letters';
        }

        return view('receipts.show', [
            'receipt' => $receipt->load($relationships),
            'settings' => $this->companySettingsForBusiness($receipt->business_id),
            'signatory' => $this->defaultSignatoryForBusiness($receipt->business_id),
            'paymentMethods' => PaymentMethod::withoutGlobalScope('business')
                ->where('business_id', $receipt->business_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function download(Receipt $receipt)
    {
        return $this->pdf($receipt)->download($receipt->receipt_number.'.pdf');
    }

    public function emailForm(Receipt $receipt)
    {
        return view('documents.email', ['document' => $receipt->load('invoice.client'), 'type' => 'receipt']);
    }

    public function sendEmail(Request $request, Receipt $receipt)
    {
        $data = $request->validate(['subject' => ['required', 'string'], 'message' => ['required', 'string']]);
        $receipt->load('invoice.client');
        $email = $receipt->invoice->client->email;
        try {
            $this->outgoingMail->sendRaw(
                $email,
                $data['subject'],
                $data['message'],
                fn ($mail) => $mail->attachData($this->pdf($receipt)->output(), $receipt->receipt_number.'.pdf', ['mime' => 'application/pdf']),
                $receipt->business_id,
                requireProfileSender: true,
            );
            $receipt->emailLogs()->create($data + ['recipient_email' => $email, 'status' => 'sent', 'sent_at' => now()]);
            $receipt->update(['sent_at' => now()]);

            return redirect()->route('receipts.show', $receipt)->with('status', 'Receipt emailed.');
        } catch (\Throwable $e) {
            $receipt->emailLogs()->create($data + ['recipient_email' => $email, 'status' => 'failed', 'error' => $e->getMessage()]);

            return back()->withErrors(['email' => 'Email failed: '.$e->getMessage()]);
        }
    }

    private function pdf(Receipt $receipt)
    {
        $receipt->load('invoice.client');

        return Pdf::loadView('pdf.document', [
            'type' => 'Receipt',
            'document' => $receipt,
            'settings' => $this->companySettingsForBusiness($receipt->business_id),
            'signatory' => $this->defaultSignatoryForBusiness($receipt->business_id),
            'paymentMethods' => PaymentMethod::withoutGlobalScope('business')->where('business_id', $receipt->business_id)->where('is_active', true)->get(),
        ]);
    }

    private function companySettingsForBusiness(?int $businessId): ?CompanySetting
    {
        if (! $businessId) {
            return CompanySetting::first();
        }

        return CompanySetting::withoutGlobalScope('business')->firstOrCreate(
            ['business_id' => $businessId],
            $this->defaultCompanySettings()
        );
    }

    private function defaultCompanySettings(): array
    {
        $defaults = ['company_name' => ActiveBusiness::current()?->name ?? 'BAMA'];

        foreach ([
            'primary_color' => CompanySetting::DEFAULT_PRIMARY_COLOR,
            'secondary_color' => CompanySetting::DEFAULT_SECONDARY_COLOR,
            'accent_color' => CompanySetting::DEFAULT_ACCENT_COLOR,
        ] as $column => $color) {
            if (Schema::hasColumn('company_settings', $column)) {
                $defaults[$column] = $color;
            }
        }

        return $defaults;
    }

    private function defaultSignatoryForBusiness(?int $businessId): ?Signatory
    {
        if (! Schema::hasTable('signatories')) {
            return null;
        }

        $query = $businessId
            ? Signatory::withoutGlobalScope('business')->where('business_id', $businessId)
            : Signatory::query();

        return $query->where('is_default', true)->where('is_active', true)->first();
    }
}
