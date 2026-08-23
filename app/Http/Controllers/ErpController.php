<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientPortalInvitation;
use App\Models\CompanySetting;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\DocumentTemplate;
use App\Models\GoodsReceivedNote;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\SupplierQuote;
use App\Models\User;
use App\Models\Warranty;
use App\Services\StockService;
use App\Support\ActiveBusiness;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ErpController extends Controller
{
    public function profit()
    {
        $this->requireErp();

        $projects = Project::with('client', 'invoices', 'receiptAllocations', 'costs', 'expenses', 'supplierInvoices')->latest()->get();

        return view('erp.profit', compact('projects'));
    }

    public function procurement()
    {
        $this->requireErp();

        return view('erp.procurement', [
            'suppliers' => Supplier::with('quotes.project', 'purchaseOrders.project', 'invoices.project')->latest()->paginate(12),
            'projects' => Project::orderBy('project_name')->get(),
            'purchaseOrders' => PurchaseOrder::with('supplier', 'project')->latest()->get(),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'costCenters' => CostCenter::where('is_active', true)->with('department')->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'goodsReceived' => GoodsReceivedNote::with('purchaseOrder.supplier', 'product')->latest()->limit(12)->get(),
        ]);
    }

    public function warranties()
    {
        $this->requireErp();

        return view('erp.warranties', [
            'warranties' => Warranty::with(array_filter(['client', 'project', 'site', 'claims', Schema::hasTable('letters') ? 'letters' : null]))->latest()->paginate(12),
            'clients' => Client::with('sites', 'projects')->orderBy('name')->get(),
        ]);
    }

    public function portal()
    {
        $this->requireErp();

        return view('erp.portal', [
            'invitations' => ClientPortalInvitation::with('client', 'contact', 'user')->latest()->paginate(12),
            'clients' => Client::with('contacts')->orderBy('name')->get(),
        ]);
    }

    public function templates()
    {
        $this->requireErp();

        return view('erp.templates', [
            'templates' => DocumentTemplate::latest()->paginate(12),
        ]);
    }

    public function reports()
    {
        $this->requireErp();

        $projects = Project::with('client', 'invoices', 'costs', 'expenses', 'supplierInvoices', 'receiptAllocations')->get();
        $revenue = $projects->sum(fn ($project) => $project->revenue());
        $costs = $projects->sum(fn ($project) => $project->actualCost());
        $collected = $projects->sum(fn ($project) => $project->collected());
        $supplierOutstanding = SupplierInvoice::query()->selectRaw('SUM(GREATEST(total - amount_paid, 0)) as total')->value('total') ?? 0;
        $taxDue = Invoice::source()->sum('tax_total');

        return view('erp.reports', compact('projects', 'revenue', 'costs', 'collected', 'supplierOutstanding', 'taxDue'));
    }

    public function storeProjectCost(Request $request, Project $project)
    {
        $this->requireErp();
        $project->costs()->create($request->validate([
            'category' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'expected_amount' => ['nullable', 'numeric', 'min:0'],
            'actual_amount' => ['nullable', 'numeric', 'min:0'],
        ]));

        return back()->with('status', 'Project cost saved.');
    }

    public function storeProjectExpense(Request $request, Project $project)
    {
        $this->requireErp();
        $project->expenses()->create($request->validate([
            'expense_date' => ['required', 'date'],
            'category' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]));

        return back()->with('status', 'Project expense saved.');
    }

    public function storeProjectDocument(Request $request, Project $project)
    {
        $this->requireErp();
        $data = $request->validate([
            'document_template_id' => ['nullable', Rule::exists('document_templates', 'id')->where('business_id', ActiveBusiness::id())],
            'document_type' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'status' => ['required', 'string', 'max:255'],
        ]);

        if (! empty($data['document_template_id']) && empty($data['content'])) {
            $template = DocumentTemplate::find($data['document_template_id']);
            $data['content'] = $this->renderTemplate($template?->content ?? '', $project);
        }

        $project->documents()->create($data);

        return back()->with('status', 'Project document saved.');
    }

    public function storeHandover(Request $request, Project $project)
    {
        $this->requireErp();
        $data = $request->validate([
            'handover_date' => ['nullable', 'date'],
            'status' => ['required', 'string', 'max:255'],
            'signature_name' => ['nullable', 'string', 'max:255'],
            'signature_data' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'checklist' => ['nullable', 'array'],
            'checklist.*' => ['nullable', 'string', 'max:255'],
        ]);

        $checklist = array_filter($data['checklist'] ?? []);
        unset($data['checklist']);
        $handover = $project->handovers()->create($data);
        foreach ($checklist as $label) {
            $handover->checklistItems()->create(['label' => $label]);
        }

        return back()->with('status', 'Handover record saved.');
    }

    public function storeSupplier(Request $request)
    {
        $this->requireErp();
        Supplier::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'kra_pin' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
        ]));

        return back()->with('status', 'Supplier saved.');
    }

    public function storeSupplierQuote(Request $request)
    {
        $this->requireErp();
        SupplierQuote::create($request->validate([
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('business_id', ActiveBusiness::id())],
            'project_id' => ['nullable', Rule::exists('projects', 'id')->where('business_id', ActiveBusiness::id())],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('business_id', ActiveBusiness::id())],
            'cost_center_id' => ['nullable', Rule::exists('cost_centers', 'id')->where('business_id', ActiveBusiness::id())],
            'quote_number' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]));

        return back()->with('status', 'Supplier quote saved.');
    }

    public function storePurchaseOrder(Request $request)
    {
        $this->requireErp();
        PurchaseOrder::create($request->validate([
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('business_id', ActiveBusiness::id())],
            'project_id' => ['nullable', Rule::exists('projects', 'id')->where('business_id', ActiveBusiness::id())],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('business_id', ActiveBusiness::id())],
            'cost_center_id' => ['nullable', Rule::exists('cost_centers', 'id')->where('business_id', ActiveBusiness::id())],
            'po_number' => ['required', 'string', 'max:255'],
            'order_date' => ['nullable', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]));

        return back()->with('status', 'Purchase order saved.');
    }

    public function storeGoodsReceived(Request $request)
    {
        $this->requireErp();
        $data = $request->validate([
            'purchase_order_id' => ['required', Rule::exists('purchase_orders', 'id')->where('business_id', ActiveBusiness::id())],
            'product_id' => ['nullable', Rule::exists('products', 'id')->where('business_id', ActiveBusiness::id())],
            'received_date' => ['required', 'date'],
            'quantity_received' => ['nullable', 'numeric', 'min:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'received_by' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['quantity_received'] ??= 0;
        $data['unit_cost'] ??= 0;
        $data['line_total'] = (float) $data['quantity_received'] * (float) $data['unit_cost'];
        $note = GoodsReceivedNote::create($data);

        if (! empty($data['product_id']) && (float) $data['quantity_received'] > 0) {
            app(StockService::class)->receive(
                Product::findOrFail($data['product_id']),
                (float) $data['quantity_received'],
                $note,
                'Goods received',
                'Stock received through procurement.'
            );
        }

        return back()->with('status', 'Goods received note saved.');
    }

    public function storeSupplierInvoice(Request $request)
    {
        $this->requireErp();
        SupplierInvoice::create($request->validate([
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('business_id', ActiveBusiness::id())],
            'project_id' => ['nullable', Rule::exists('projects', 'id')->where('business_id', ActiveBusiness::id())],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('business_id', ActiveBusiness::id())],
            'cost_center_id' => ['nullable', Rule::exists('cost_centers', 'id')->where('business_id', ActiveBusiness::id())],
            'purchase_order_id' => ['nullable', Rule::exists('purchase_orders', 'id')->where('business_id', ActiveBusiness::id())],
            'invoice_number' => ['required', 'string', 'max:255'],
            'invoice_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'total' => ['required', 'numeric', 'min:0'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]));

        return back()->with('status', 'Supplier invoice saved.');
    }

    public function storeSupplierPayment(Request $request, SupplierInvoice $supplierInvoice)
    {
        $this->requireErp();
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $supplierInvoice->payments()->create($data + ['department_id' => $supplierInvoice->department_id, 'cost_center_id' => $supplierInvoice->cost_center_id]);
        $paid = $supplierInvoice->payments()->sum('amount');
        $supplierInvoice->update([
            'amount_paid' => $paid,
            'status' => $paid >= $supplierInvoice->total ? 'Paid' : 'Partial',
        ]);

        return back()->with('status', 'Supplier payment recorded.');
    }

    public function storeWarranty(Request $request)
    {
        $this->requireErp();
        Warranty::create($request->validate([
            'client_id' => ['nullable', Rule::exists('clients', 'id')->where('business_id', ActiveBusiness::id())],
            'project_id' => ['nullable', Rule::exists('projects', 'id')->where('business_id', ActiveBusiness::id())],
            'site_id' => ['nullable', Rule::exists('sites', 'id')->where('business_id', ActiveBusiness::id())],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'status' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]));

        return back()->with('status', 'Warranty saved.');
    }

    public function storeWarrantyClaim(Request $request, Warranty $warranty)
    {
        $this->requireErp();
        $warranty->claims()->create($request->validate([
            'claim_date' => ['required', 'date'],
            'status' => ['required', 'string', 'max:255'],
            'issue' => ['nullable', 'string'],
            'resolution' => ['nullable', 'string'],
        ]));
        $warranty->update(['status' => $request->input('status') === 'Resolved' ? 'Resolved' : 'Claim Open']);

        return back()->with('status', 'Warranty claim saved.');
    }

    public function storePortalInvite(Request $request)
    {
        $this->requireErp();
        ClientPortalInvitation::create($request->validate([
            'client_id' => ['required', Rule::exists('clients', 'id')->where('business_id', ActiveBusiness::id())],
            'contact_id' => ['nullable', Rule::exists('contacts', 'id')->where('business_id', ActiveBusiness::id())],
            'email' => ['required', 'email', 'max:255'],
        ]) + [
            'token' => Str::random(64),
            'status' => 'Invited',
            'invited_at' => now(),
        ]);

        return back()->with('status', 'Portal invitation created.');
    }

    public function storeTemplate(Request $request)
    {
        $this->requireErp();
        DocumentTemplate::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'output_format' => ['required', 'in:PDF,DOCX'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')]);

        return back()->with('status', 'Document template saved.');
    }

    public function downloadProjectDocument(ProjectDocument $projectDocument)
    {
        $this->requireErp();
        $projectDocument->load('template', 'project.client', 'project.site');
        $format = $projectDocument->template?->output_format ?: 'PDF';

        if ($format === 'DOCX') {
            return response()->download($this->writeDocx($projectDocument), Str::slug($projectDocument->title).'.docx')->deleteFileAfterSend(true);
        }

        return Pdf::loadView('pdf.project-document', [
            'document' => $projectDocument,
            'settings' => $this->companySettingsForBusiness($projectDocument->business_id),
        ])
            ->download(Str::slug($projectDocument->title).'.pdf');
    }

    public function updateAuthSettings(Request $request, User $user)
    {
        $this->requireErp();
        $request->validate([
            'enable_password_login' => ['nullable', 'boolean'],
            'enable_otp_login' => ['nullable', 'boolean'],
            'enable_magic_link_login' => ['nullable', 'boolean'],
        ]);

        $user->update([
            'enable_password_login' => $request->boolean('enable_password_login'),
            'enable_otp_login' => $request->boolean('enable_otp_login'),
            'enable_magic_link_login' => $request->boolean('enable_magic_link_login'),
        ]);

        return back()->with('status', 'Authentication settings updated.');
    }

    private function renderTemplate(string $content, Project $project): string
    {
        $project->loadMissing('client', 'site');

        return strtr($content, [
            '{{client}}' => $project->client?->name ?? '',
            '{{site}}' => $project->site?->site_name ?? '',
            '{{project}}' => $project->project_name,
            '{{balance}}' => number_format(max($project->revenue() - $project->collected(), 0), 2),
        ]);
    }

    private function writeDocx(ProjectDocument $document): string
    {
        $path = tempnam(sys_get_temp_dir(), 'docx_');
        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
        $body = collect(preg_split("/\r\n|\n|\r/", $document->content ?: $document->title))
            ->map(fn ($line) => '<w:p><w:r><w:t>'.htmlspecialchars($line, ENT_XML1).'</w:t></w:r></w:p>')
            ->implode('');
        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'.$body.'</w:body></w:document>');
        $zip->close();

        return $path;
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

    private function requireErp(): void
    {
        abort_unless(Schema::hasTable('projects') && Schema::hasTable('project_costs'), 404);
    }
}
