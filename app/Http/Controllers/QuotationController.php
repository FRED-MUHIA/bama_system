<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Quotation;
use App\Models\Signatory;
use App\Services\DocumentService;
use App\Services\InvoicePosOrderService;
use App\Services\OutgoingMailService;
use App\Support\ActiveBusiness;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class QuotationController extends Controller
{
    public function __construct(private DocumentService $documents, private InvoicePosOrderService $invoiceOrders, private OutgoingMailService $outgoingMail) {}

    public function index()
    {
        return view('quotations.index', ['quotations' => Quotation::with('client')->latest()->paginate(12)]);
    }

    public function create()
    {
        return view('quotations.form', $this->formData(new Quotation(['quotation_date' => now(), 'valid_until' => now()->addDays(14)])));
    }

    public function show(Quotation $quotation)
    {
        $relationships = ['client', 'items', 'emailLogs'];
        if (Quotation::supportsProjectLinks()) {
            $relationships[] = 'site';
            $relationships[] = 'project';
            $relationships[] = 'contact';
        }

        return view('quotations.show', [
            'quotation' => $quotation->load($relationships),
            'settings' => $this->companySettingsForBusiness($quotation->business_id),
            'signatory' => $this->defaultSignatoryForBusiness($quotation->business_id),
            'paymentMethods' => PaymentMethod::withoutGlobalScope('business')
                ->where('business_id', $quotation->business_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function edit(Quotation $quotation)
    {
        return view('quotations.form', $this->formData($quotation->load('items')));
    }

    public function store(Request $request)
    {
        $quotation = DB::transaction(fn () => $this->saveQuotation(new Quotation, $request));

        return redirect()->route('quotations.show', $quotation)->with('status', 'Quotation saved.');
    }

    public function update(Request $request, Quotation $quotation)
    {
        DB::transaction(fn () => $this->saveQuotation($quotation, $request));

        return redirect()->route('quotations.show', $quotation)->with('status', 'Quotation updated.');
    }

    public function destroy(Quotation $quotation)
    {
        $quotation->delete();

        return redirect()->route('quotations.index')->with('status', 'Quotation deleted.');
    }

    public function download(Quotation $quotation)
    {
        return $this->pdf($quotation)->download($quotation->quotation_number.'.pdf');
    }

    public function emailForm(Quotation $quotation)
    {
        return view('documents.email', ['document' => $quotation->load('client'), 'type' => 'quotation']);
    }

    public function sendEmail(Request $request, Quotation $quotation)
    {
        $data = $request->validate([
            'to' => ['required', 'email'],
            'cc' => ['nullable', 'string', 'max:2000'],
            'subject' => ['required', 'string'],
            'message' => ['required', 'string'],
        ]);
        $cc = $this->validatedEmailList($data['cc'] ?? null);
        $logData = collect($data)->only(['subject', 'message'])->all();
        $quotation->load('client');

        try {
            $this->outgoingMail->sendRaw(
                $data['to'],
                $data['subject'],
                $data['message'],
                fn ($mail) => $mail->attachData($this->pdf($quotation)->output(), $quotation->quotation_number.'.pdf', ['mime' => 'application/pdf']),
                $quotation->business_id,
                requireProfileSender: true,
                cc: $cc,
            );
            $quotation->emailLogs()->create($logData + ['recipient_email' => $data['to'], 'status' => 'sent', 'sent_at' => now()]);
            $quotation->update(['sent_at' => now(), 'status' => 'sent']);

            return redirect()->route('quotations.show', $quotation)->with('status', 'Quotation emailed.');
        } catch (\Throwable $e) {
            $quotation->emailLogs()->create($logData + ['recipient_email' => $data['to'], 'status' => 'failed', 'error' => $e->getMessage()]);

            return back()->withErrors(['email' => 'Email failed: '.$this->outgoingMail->userFacingError($e)]);
        }
    }

    public function convert(Quotation $quotation)
    {
        $invoice = DB::transaction(function () use ($quotation) {
            $quotation->load('items');
            $invoiceData = [
                'client_id' => $quotation->client_id,
                'quotation_id' => $quotation->id,
                'invoice_number' => $this->documents->number('invoice'),
                'public_token' => Str::random(48),
                'invoice_date' => now(),
                'due_date' => now()->addDays(14),
                'payment_status' => 'unpaid',
                'subtotal' => $quotation->subtotal,
                'discount_total' => $quotation->discount_total,
                'tax_total' => $quotation->tax_total,
                'total' => $quotation->total,
                'amount_paid' => 0,
                'balance' => $quotation->total,
                'terms' => $quotation->terms,
                'notes' => $quotation->notes,
            ];

            if (Quotation::supportsProjectLinks() && Invoice::supportsProjectLinks()) {
                $invoiceData += [
                    'site_id' => $quotation->site_id,
                    'project_id' => $quotation->project_id,
                    'contact_id' => $quotation->contact_id,
                ];
            }

            $invoice = Invoice::create($invoiceData);
            foreach ($quotation->items as $item) {
                $invoice->items()->create($item->only('title', 'description', 'quantity', 'unit_price', 'discount', 'tax_rate', 'line_total'));
            }
            $this->invoiceOrders->sync($invoice);
            $quotation->update(['status' => 'converted']);

            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice)->with('status', 'Quotation converted to invoice.');
    }

    private function saveQuotation(Quotation $quotation, Request $request): Quotation
    {
        $data = $this->validated($request);
        $data['client_id'] = $this->resolveClient($data);
        $items = $this->documents->normalizeItems($data['items']);
        unset($data['items'], $data['client_mode'], $data['client']);
        if (! Quotation::supportsProjectLinks()) {
            unset($data['site_id'], $data['project_id'], $data['contact_id']);
        }
        $totals = $this->documents->totals($items);
        $quotation->fill($data + [
            'quotation_number' => $quotation->quotation_number ?: $this->documents->number('quotation'),
            'subtotal' => $totals['subtotal'],
            'discount_total' => $totals['discountTotal'],
            'tax_total' => $totals['taxTotal'],
            'total' => $totals['total'],
        ])->save();

        $quotation->items()->delete();
        foreach ($items as $item) {
            $quotation->items()->create($item + ['line_total' => $this->documents->lineTotal($item)]);
        }

        return $quotation;
    }

    private function validated(Request $request): array
    {
        $rules = [
            'client_mode' => ['required', 'in:existing,new'],
            'client_id' => ['required_if:client_mode,existing', 'nullable', Rule::exists('clients', 'id')->where('business_id', ActiveBusiness::id())],
            'client.name' => ['required_if:client_mode,new', 'nullable', 'string', 'max:255'],
            'client.phone' => ['nullable', 'string', 'max:100'],
            'client.email' => ['nullable', 'email', 'max:255'],
            'client.company_name' => ['nullable', 'string', 'max:255'],
            'client.address' => ['nullable', 'string'],
            'client.type' => ['nullable', 'in:company,individual'],
            'client.billing_address' => ['nullable', 'string'],
            'client.kra_pin' => ['nullable', 'string', 'max:100'],
            'client.notes' => ['nullable', 'string'],
            'quotation_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date'],
            'status' => ['required', 'string'],
            'terms' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.title' => ['nullable', 'string', 'max:255'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0'],
        ];

        if (Quotation::supportsProjectLinks()) {
            $rules['site_id'] = ['nullable', Rule::exists('sites', 'id')->where('business_id', ActiveBusiness::id())];
            $rules['project_id'] = ['nullable', Rule::exists('projects', 'id')->where('business_id', ActiveBusiness::id())];
            $rules['contact_id'] = ['nullable', Rule::exists('contacts', 'id')->where('business_id', ActiveBusiness::id())];
        }

        return $request->validate($rules);
    }

    private function resolveClient(array $data): int
    {
        if (($data['client_mode'] ?? 'existing') === 'new') {
            if (! Client::supportsCompanyStructure()) {
                unset($data['client']['type'], $data['client']['billing_address'], $data['client']['kra_pin']);
            }

            $client = Client::create($data['client']);

            return $client->id;
        }

        return (int) $data['client_id'];
    }

    private function formData(Quotation $quotation): array
    {
        $clients = Quotation::supportsProjectLinks()
            ? Client::with('sites', 'projects', 'contacts')->orderBy('name')->get()
            : Client::orderBy('name')->get();

        return [
            'quotation' => $quotation,
            'clients' => $clients,
            'settings' => $settings = $this->companySettingsForBusiness(ActiveBusiness::id()),
            'taxRate' => $settings?->tax_rate ?? 0,
            'projectLinksEnabled' => Quotation::supportsProjectLinks(),
        ];
    }

    private function pdf(Quotation $quotation)
    {
        return Pdf::loadView('pdf.document', [
            'type' => 'Quotation',
            'document' => $quotation->load(array_filter(['client', 'items', Quotation::supportsProjectLinks() ? 'site' : null, Quotation::supportsProjectLinks() ? 'project' : null, Quotation::supportsProjectLinks() ? 'contact' : null])),
            'settings' => $this->companySettingsForBusiness($quotation->business_id),
            'signatory' => $this->defaultSignatoryForBusiness($quotation->business_id),
            'paymentMethods' => PaymentMethod::withoutGlobalScope('business')->where('business_id', $quotation->business_id)->where('is_active', true)->get(),
            'verificationUrl' => null,
            'qrCode' => null,
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
        $defaults = ['company_name' => ActiveBusiness::current()?->name ?? 'Bama'];

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

        return (clone $query)->where('is_default', true)->where('is_active', true)->first()
            ?: (clone $query)->where('is_active', true)->latest()->first();
    }
}
