<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\InvoiceAllocation;
use App\Models\PaymentMethod;
use App\Models\Project;
use App\Models\ReceiptAllocation;
use App\Models\Signatory;
use App\Models\Site;
use App\Services\CostAccountingService;
use App\Services\DocumentService;
use App\Services\InvoicePartPaymentService;
use App\Services\InvoicePosOrderService;
use App\Services\InvoiceVerificationService;
use App\Services\OutgoingMailService;
use App\Support\ActiveBusiness;
use App\Support\ActiveTenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\PrintingBranding\Models\ProductionJob;

class InvoiceController extends Controller
{
    public function __construct(
        private DocumentService $documents,
        private InvoicePosOrderService $invoiceOrders,
        private InvoicePartPaymentService $partPayments,
        private InvoiceVerificationService $verification,
        private OutgoingMailService $outgoingMail,
    ) {}

    public function index()
    {
        $relationships = ['client'];
        if (Invoice::supportsPartPayments()) {
            $relationships[] = 'partPaymentInvoices';
        }

        return view('invoices.index', ['invoices' => Invoice::source()->with($relationships)->latest()->paginate(12)]);
    }

    public function create()
    {
        return view('invoices.form', $this->formData(new Invoice(['invoice_date' => now(), 'due_date' => now()->addDays(14)])));
    }

    public function show(Invoice $invoice)
    {
        $methods = PaymentMethod::withoutGlobalScope('business')
            ->where('business_id', $invoice->business_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('invoices.show', [
            'invoice' => $invoice->load($this->invoiceRelationships()),
            'methods' => $methods,
            'paymentMethods' => $methods,
            'settings' => $this->companySettingsForBusiness($invoice->business_id),
            'signatory' => $this->defaultSignatoryForBusiness($invoice->business_id),
            'remainingPartPaymentBalance' => Invoice::supportsPartPayments() && ! $invoice->isPartPayment() ? $this->partPayments->getRemainingBalance($invoice->id) : null,
            'sourceInvoices' => Invoice::supportsInvoiceTypes() ? Invoice::source()->whereKeyNot($invoice->id)->orderByDesc('id')->limit(30)->get() : collect(),
            'verificationUrl' => $this->verification->url($invoice),
            'qrCode' => $this->verification->qrCodeDataUri($invoice, 150),
        ]);
    }

    public function edit(Invoice $invoice)
    {
        if ($invoice->isPartPayment()) {
            return back()->withErrors(['invoice' => 'Part payment invoices cannot be edited as source invoices.']);
        }

        return view('invoices.form', $this->formData($invoice->load('items')));
    }

    public function store(Request $request)
    {
        $invoice = DB::transaction(fn () => $this->saveInvoice(new Invoice, $request));

        return redirect()->route('invoices.show', $invoice)->with('status', 'Invoice saved.');
    }

    public function update(Request $request, Invoice $invoice)
    {
        if ($invoice->isPartPayment()) {
            return back()->withErrors(['invoice' => 'Part payment invoices cannot be edited as source invoices.']);
        }

        DB::transaction(fn () => $this->saveInvoice($invoice, $request));

        return redirect()->route('invoices.show', $invoice)->with('status', 'Invoice updated.');
    }

    public function destroy(Invoice $invoice)
    {
        $data = request()->validate([
            'delete_pin' => ['required', 'digits:4'],
        ]);

        if ($data['delete_pin'] !== '1483') {
            return back()->withErrors(['delete_pin' => 'The invoice delete PIN is incorrect.']);
        }

        $invoice->delete();

        return redirect()->route('invoices.index')->with('status', 'Invoice deleted.');
    }

    public function download(Invoice $invoice)
    {
        return $this->pdf($invoice)->download($invoice->invoice_number.'.pdf');
    }

    public function emailForm(Invoice $invoice)
    {
        return view('documents.email', ['document' => $invoice->load('client'), 'type' => 'invoice']);
    }

    public function publicShow(string $token)
    {
        $invoice = Invoice::withoutGlobalScope('business')->where('public_token', $token)->with('items')->firstOrFail();
        $invoice->setRelation('client', Client::withoutGlobalScope('business')->find($invoice->client_id));
        if ($invoice->isPartPayment()) {
            $invoice->setRelation('parentInvoice', Invoice::withoutGlobalScope('business')->find($invoice->parent_invoice_id));
        }
        if (Invoice::supportsProjectLinks()) {
            $invoice->setRelation('site', Site::withoutGlobalScope('business')->find($invoice->site_id));
            $invoice->setRelation('project', Project::withoutGlobalScope('business')->find($invoice->project_id));
            $invoice->setRelation('contact', Contact::withoutGlobalScope('business')->find($invoice->contact_id));
        }
        $settings = $this->companySettingsForBusiness($invoice->business_id);

        return view('invoices.public', [
            'invoice' => $invoice,
            'settings' => $settings,
            'signatory' => $this->defaultSignatoryForBusiness($invoice->business_id),
            'paymentMethods' => PaymentMethod::withoutGlobalScope('business')->where('business_id', $invoice->business_id)->where('is_active', true)->get(),
            'verificationUrl' => $this->verification->url($invoice),
            'qrCode' => $this->verification->qrCodeDataUri($invoice, 170),
        ]);
    }

    public function publicDownload(string $token)
    {
        $invoice = Invoice::withoutGlobalScope('business')->where('public_token', $token)->firstOrFail();

        return $this->pdf($invoice)->download($invoice->invoice_number.'.pdf');
    }

    public function sendEmail(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'to' => ['required', 'email'],
            'cc' => ['nullable', 'string', 'max:2000'],
            'subject' => ['required', 'string'],
            'message' => ['required', 'string'],
        ]);
        $cc = $this->validatedEmailList($data['cc'] ?? null);
        $logData = collect($data)->only(['subject', 'message'])->all();
        $invoice->load('client');
        try {
            $this->outgoingMail->sendRaw(
                $data['to'],
                $data['subject'],
                $data['message'],
                fn ($mail) => $mail->attachData($this->pdf($invoice)->output(), $invoice->invoice_number.'.pdf', ['mime' => 'application/pdf']),
                $invoice->business_id,
                requireProfileSender: true,
                cc: $cc,
            );
            $invoice->emailLogs()->create($logData + ['recipient_email' => $data['to'], 'status' => 'sent', 'sent_at' => now()]);
            $invoice->update(['sent_at' => now()]);

            return redirect()->route('invoices.show', $invoice)->with('status', 'Invoice emailed.');
        } catch (\Throwable $e) {
            $invoice->emailLogs()->create($logData + ['recipient_email' => $data['to'], 'status' => 'failed', 'error' => $e->getMessage()]);

            return back()->withErrors(['email' => 'Email failed: '.$this->outgoingMail->userFacingError($e)]);
        }
    }

    public function recordPayment(Request $request, Invoice $invoice)
    {
        if ($invoice->isPartPayment()) {
            return back()->withErrors(['amount' => 'Payments must be recorded against the parent invoice.']);
        }

        $data = $request->validate([
            'payment_method_id' => ['nullable', Rule::exists('payment_methods', 'id')->where('business_id', ActiveBusiness::id())],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$invoice->balance],
            'payment_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $data += ['department_id' => $invoice->department_id, 'cost_center_id' => $invoice->cost_center_id];

        $receipt = DB::transaction(function () use ($invoice, $data) {
            $payment = $invoice->payments()->create($data);
            $paid = $invoice->payments()->sum('amount');
            $balance = max($invoice->total - $paid, 0);
            $invoice->update([
                'amount_paid' => $paid,
                'balance' => $balance,
                'payment_status' => $balance <= 0 ? 'paid' : 'partial',
            ]);
            $this->invoiceOrders->sync($invoice);

            $receiptData = [
                'payment_id' => $payment->id,
                'receipt_number' => $this->documents->number('receipt'),
                'amount_paid' => $payment->amount,
                'balance_remaining' => $balance,
                'status' => $balance <= 0 ? 'Paid' : 'Partial',
                'payment_method' => $payment->paymentMethod?->name,
                'payment_date' => $payment->payment_date,
            ];

            if (Schema::hasColumn('receipts', 'project_id') && $invoice->project_id) {
                $receiptData['project_id'] = $invoice->project_id;
            }

            $receipt = $invoice->receipts()->create($receiptData);

            if (Schema::hasTable('receipt_allocations')) {
                ReceiptAllocation::create([
                    'business_id' => $invoice->business_id,
                    'receipt_id' => $receipt->id,
                    'invoice_id' => $invoice->id,
                    'project_id' => $invoice->project_id,
                    'amount' => $payment->amount,
                ]);
            }

            return $receipt;
        });

        return redirect()->route('receipts.show', $receipt)->with('status', 'Payment recorded and receipt generated.');
    }

    public function storeAdvancedInvoice(Request $request)
    {
        if (! Invoice::supportsInvoiceTypes() || ! Invoice::supportsAllocations()) {
            return back()->withErrors(['invoice_type' => 'Advanced invoice engine is not ready yet. Please run the latest migrations.']);
        }

        $data = $request->validate([
            'source_invoice_ids' => ['required', 'array', 'min:1'],
            'source_invoice_ids.*' => ['required', Rule::exists('invoices', 'id')->where('business_id', ActiveBusiness::id())],
            'invoice_type' => ['required', 'in:PART_PAYMENT,STAGE_PAYMENT,VAT_ONLY,BALANCE,COMBINED'],
            'allocation_mode' => ['required', 'in:percentage,fixed,remaining,tax_only'],
            'percentage' => ['nullable', 'numeric', 'min:0.01', 'max:100'],
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $advancedInvoice = DB::transaction(function () use ($data) {
            $sources = Invoice::query()->source()->whereIn('id', $data['source_invoice_ids'])->lockForUpdate()->get();
            abort_if($sources->isEmpty(), 422, 'No source invoices selected.');

            $clientId = $sources->first()->client_id;
            $allocations = [];
            $totalAllocation = 0;

            foreach ($sources as $source) {
                $alreadyAllocated = (float) InvoiceAllocation::where('source_invoice_id', $source->id)->sum('allocated_amount');
                $remaining = max((float) $source->total - $alreadyAllocated, 0);
                $amount = match ($data['allocation_mode']) {
                    'percentage' => round(((float) $source->total * (float) ($data['percentage'] ?? 0)) / 100, 2),
                    'fixed' => count($sources) === 1 ? round((float) ($data['amount'] ?? 0), 2) : round((float) ($data['amount'] ?? 0) / max(count($sources), 1), 2),
                    'tax_only' => round((float) $source->tax_total, 2),
                    default => round($remaining, 2),
                };

                if ($amount <= 0 || $amount > $remaining) {
                    throw ValidationException::withMessages([
                        'amount' => 'Allocation for '.$source->invoice_number.' exceeds remaining balance of '.number_format($remaining, 2).'.',
                    ]);
                }

                $allocations[] = ['source' => $source, 'amount' => $amount];
                $totalAllocation += $amount;
            }

            $first = $sources->first();
            $invoiceData = [
                'business_id' => $first->business_id,
                'client_id' => $clientId,
                'parent_invoice_id' => $sources->count() === 1 ? $first->id : null,
                'part_payment_amount' => $totalAllocation,
                'invoice_type' => $data['invoice_type'],
                'invoice_number' => $this->documents->number('invoice'),
                'public_token' => Str::random(48),
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'payment_status' => strtolower($data['invoice_type']),
                'subtotal' => 0,
                'discount_total' => 0,
                'tax_total' => 0,
                'total' => 0,
                'amount_paid' => 0,
                'balance' => 0,
                'notes' => $data['notes'] ?? 'Allocation invoice generated from source invoices.',
            ];

            if (Invoice::supportsProjectLinks()) {
                $invoiceData += [
                    'site_id' => $first->site_id,
                    'project_id' => $first->project_id,
                    'contact_id' => $first->contact_id,
                ];
            }

            $invoice = Invoice::create($invoiceData);
            foreach ($allocations as $allocation) {
                InvoiceAllocation::create([
                    'business_id' => $invoice->business_id,
                    'invoice_id' => $invoice->id,
                    'source_invoice_id' => $allocation['source']->id,
                    'allocated_amount' => $allocation['amount'],
                ]);
            }

            return $invoice;
        });

        return redirect()->route('invoices.show', $advancedInvoice)->with('status', 'Advanced allocation invoice created.');
    }

    public function storePartPayment(Request $request, Invoice $invoice)
    {
        if (! Invoice::supportsPartPayments()) {
            return back()->withErrors(['amount' => 'Part payment invoices are not ready yet. Please run the latest database migrations.']);
        }

        if ($invoice->isPartPayment()) {
            return back()->withErrors(['parent_invoice_id' => 'Create part payment invoices from the parent invoice.']);
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'terms' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $partPaymentInvoice = DB::transaction(function () use ($invoice, $data) {
            $parent = Invoice::query()->source()->lockForUpdate()->findOrFail($invoice->id);
            $amount = round((float) $data['amount'], 2);

            $this->partPayments->validatePartPaymentAmount($parent->id, $amount);

            $invoiceData = [
                'business_id' => $parent->business_id,
                'client_id' => $parent->client_id,
                'quotation_id' => $parent->quotation_id,
                'parent_invoice_id' => $parent->id,
                'part_payment_amount' => $amount,
                'invoice_number' => $this->documents->number('invoice'),
                'public_token' => Str::random(48),
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? $parent->due_date,
                'payment_status' => 'part_payment',
                'subtotal' => 0,
                'discount_total' => 0,
                'tax_total' => 0,
                'total' => 0,
                'amount_paid' => 0,
                'balance' => 0,
                'terms' => $data['terms'] ?? $parent->terms,
                'notes' => $data['notes'] ?? 'Part payment allocation for invoice '.$parent->invoice_number,
            ];

            if (Invoice::supportsProjectLinks()) {
                $invoiceData += [
                    'site_id' => $parent->site_id,
                    'project_id' => $parent->project_id,
                    'contact_id' => $parent->contact_id,
                ];
            }

            $partPayment = Invoice::create($invoiceData);

            if (Invoice::supportsAllocations()) {
                InvoiceAllocation::create([
                    'business_id' => $parent->business_id,
                    'invoice_id' => $partPayment->id,
                    'source_invoice_id' => $parent->id,
                    'allocated_amount' => $amount,
                ]);
            }

            return $partPayment;
        });

        return redirect()->route('invoices.show', $partPaymentInvoice)->with('status', 'Part payment invoice created.');
    }

    private function saveInvoice(Invoice $invoice, Request $request): Invoice
    {
        $data = $this->validated($request);
        if (! empty($data['project_id']) && empty($data['cost_center_id'])) {
            $center = app(CostAccountingService::class)->ensureProjectCostCenter(Project::findOrFail($data['project_id']));
            $data['cost_center_id'] = $center->id;
            $data['department_id'] ??= $center->department_id;
        }
        $data['client_id'] = $this->resolveClient($data);
        $items = $this->documents->normalizeItems($data['items']);
        $printingJob = $this->printingJob($data['printing_job_id'] ?? null);
        $printingInvoiceType = $data['printing_invoice_type'] ?? null;
        unset($data['items'], $data['client_mode'], $data['client'], $data['printing_job_id'], $data['printing_invoice_type']);
        if (! Invoice::supportsProjectLinks()) {
            unset($data['site_id'], $data['project_id'], $data['contact_id']);
        }
        if (! Invoice::supportsInvoiceTypes()) {
            unset($data['invoice_type']);
        }
        $totals = $this->documents->totals($items);
        $paid = $invoice->exists ? $invoice->payments()->sum('amount') : 0;
        $balance = max($totals['total'] - $paid, 0);
        $invoiceData = $data + [
            'invoice_number' => $invoice->invoice_number ?: $this->documents->number('invoice'),
            'public_token' => $invoice->public_token ?: Str::random(48),
            'subtotal' => $totals['subtotal'],
            'discount_total' => $totals['discountTotal'],
            'tax_total' => $totals['taxTotal'],
            'total' => $totals['total'],
            'amount_paid' => $paid,
            'balance' => $balance,
            'payment_status' => $balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
        ];

        if (Invoice::supportsInvoiceTypes()) {
            $invoiceData['invoice_type'] = 'STANDARD';
        }

        if ($printingJob) {
            $invoiceData['client_id'] = $printingJob->client_id;
            $invoiceData['quotation_id'] = $printingJob->quotation_id ?: ($invoiceData['quotation_id'] ?? null);
            $invoiceData['industry_module'] = 'printing_branding';
            $invoiceData['industry_reference'] = $printingJob->job_number;
            $invoiceData['industry_context'] = [
                'invoice_type' => $printingInvoiceType ?: data_get($invoice->industry_context, 'invoice_type', 'Final Invoice'),
                'production_job_id' => $printingJob->id,
                'job_number' => $printingJob->job_number,
                'product_name' => $printingJob->product_name,
                'quantity' => (float) $printingJob->quantity,
                'specifications' => $printingJob->specifications ?? [],
                'delivery_date' => $printingJob->delivery_date?->toDateString(),
                'priority' => $printingJob->priority,
                'job_status' => $printingJob->status,
                'machine' => $printingJob->machine?->name,
                'ticket_number' => $printingJob->ticket?->ticket_number,
            ];
        }

        $invoice->fill($invoiceData)->save();

        $invoice->items()->delete();
        foreach ($items as $item) {
            $invoice->items()->create($item + ['line_total' => $this->documents->lineTotal($item)]);
        }
        $this->invoiceOrders->sync($invoice);

        return $invoice;
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
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
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

        if ($this->printingInvoicesAvailable()) {
            $rules['printing_job_id'] = ['nullable', Rule::exists('printing_jobs', 'id')->where('business_id', ActiveBusiness::id())];
            $rules['printing_invoice_type'] = ['nullable', 'in:Proforma Invoice,Deposit Invoice,Stage Invoice,Balance Invoice,Final Invoice'];
        }

        if (Invoice::supportsProjectLinks()) {
            $rules['site_id'] = ['nullable', Rule::exists('sites', 'id')->where('business_id', ActiveBusiness::id())];
            $rules['project_id'] = ['nullable', Rule::exists('projects', 'id')->where('business_id', ActiveBusiness::id())];
            $rules['contact_id'] = ['nullable', Rule::exists('contacts', 'id')->where('business_id', ActiveBusiness::id())];
        }
        if (Schema::hasTable('departments')) {
            $rules['department_id'] = ['nullable', Rule::exists('departments', 'id')->where('business_id', ActiveBusiness::id())];
            $rules['cost_center_id'] = ['nullable', Rule::exists('cost_centers', 'id')->where('business_id', ActiveBusiness::id())];
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

    private function formData(Invoice $invoice): array
    {
        $clients = Invoice::supportsProjectLinks()
            ? Client::with('sites', 'projects', 'contacts')->orderBy('name')->get()
            : Client::orderBy('name')->get();

        return [
            'invoice' => $invoice,
            'clients' => $clients,
            'settings' => $settings = $this->companySettingsForBusiness(ActiveBusiness::id()),
            'taxRate' => $settings?->tax_rate ?? 0,
            'projectLinksEnabled' => Invoice::supportsProjectLinks(),
            'printingInvoiceEnabled' => $this->printingInvoicesAvailable(),
            'printingJobs' => $this->printingInvoiceJobs($invoice),
        ];
    }

    private function isPrintingBrandingTenant(): bool
    {
        return ActiveTenant::current()?->industry === 'printing_branding'
            || ActiveBusiness::current()?->industry === 'printing_branding';
    }

    private function printingInvoiceJobs(Invoice $invoice)
    {
        if (! $this->printingInvoicesAvailable()) {
            return collect();
        }

        $selectedJobId = data_get($invoice->industry_context, 'production_job_id');

        return ProductionJob::with('client', 'quotation', 'cost', 'machine', 'ticket')
            ->where(function ($query) use ($selectedJobId) {
                $query->whereNotIn('status', ['Cancelled'])
                    ->when($selectedJobId, fn ($query) => $query->orWhereKey($selectedJobId));
            })
            ->latest()
            ->limit(120)
            ->get();
    }

    private function printingJob(null|int|string $jobId)
    {
        if (! $jobId || ! $this->printingInvoicesAvailable()) {
            return null;
        }

        return ProductionJob::with('client', 'quotation', 'cost', 'machine', 'ticket')
            ->whereKey($jobId)
            ->firstOrFail();
    }

    private function printingInvoicesAvailable(): bool
    {
        return $this->isPrintingBrandingTenant()
            && class_exists(ProductionJob::class)
            && Schema::hasTable('printing_jobs');
    }

    private function invoiceRelationships(): array
    {
        $relationships = ['client', 'items', 'payments.paymentMethod', 'receipts', 'emailLogs'];
        if (Schema::hasTable('letters')) {
            $relationships[] = 'letters';
        }

        if (Invoice::supportsPartPayments()) {
            $relationships[] = 'parentInvoice';
            $relationships[] = 'partPaymentInvoices';
        }
        if (Invoice::supportsProjectLinks()) {
            $relationships[] = 'site';
            $relationships[] = 'project';
            $relationships[] = 'contact';
        }

        return $relationships;
    }

    private function pdf(Invoice $invoice)
    {
        $invoice->load('items');
        if (Invoice::supportsProjectLinks()) {
            $invoice->setRelation('site', Site::withoutGlobalScope('business')->find($invoice->site_id));
            $invoice->setRelation('project', Project::withoutGlobalScope('business')->find($invoice->project_id));
            $invoice->setRelation('contact', Contact::withoutGlobalScope('business')->find($invoice->contact_id));
        }
        if ($invoice->isPartPayment()) {
            $invoice->setRelation('parentInvoice', Invoice::withoutGlobalScope('business')->find($invoice->parent_invoice_id));
        }
        $invoice->setRelation('client', Client::withoutGlobalScope('business')->find($invoice->client_id) ?: $invoice->client);

        return Pdf::loadView('pdf.document', [
            'type' => 'Invoice',
            'document' => $invoice,
            'settings' => $this->companySettingsForBusiness($invoice->business_id),
            'signatory' => $this->defaultSignatoryForBusiness($invoice->business_id),
            'paymentMethods' => PaymentMethod::withoutGlobalScope('business')->where('business_id', $invoice->business_id)->where('is_active', true)->get(),
            'verificationUrl' => $this->verification->url($invoice),
            'qrCode' => $this->verification->qrCodeDataUri($invoice, 150),
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
