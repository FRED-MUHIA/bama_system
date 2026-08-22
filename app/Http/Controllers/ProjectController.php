<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\DocumentTemplate;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Support\ActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index()
    {
        abort_unless(Client::supportsCompanyStructure(), 404);

        return view('projects.index', [
            'projects' => Project::with('client', 'site', 'contact')->latest()->paginate(12),
        ]);
    }

    public function create()
    {
        abort_unless(Client::supportsCompanyStructure(), 404);

        return view('projects.form', $this->formData(new Project(['status' => 'Lead'])));
    }

    public function store(Request $request)
    {
        abort_unless(Client::supportsCompanyStructure(), 404);

        $project = Project::create($this->validated($request));
        return redirect()->route('projects.show', $project)->with('status', 'Project saved.');
    }

    public function show(Project $project)
    {
        abort_unless(Client::supportsCompanyStructure(), 404);

        $relationships = ['client', 'site', 'contact', 'quotations', 'invoices.receipts'];
        $erpEnabled = Schema::hasTable('project_costs');
        if (Schema::hasTable('letters')) {
            $relationships[] = 'letters.client';
            $relationships[] = 'letters.invoice';
            $relationships[] = 'letters.receipt';
        }

        if ($erpEnabled) {
            $relationships = array_merge($relationships, [
                'costs',
                'expenses',
                'supplierQuotes.supplier',
                'purchaseOrders.supplier',
                'supplierInvoices.supplier',
                'warranties.claims',
                'documents.template',
                'handovers.checklistItems',
                'receiptAllocations.receipt',
            ]);
        }

        return view('projects.show', [
            'project' => $project->load($relationships),
            'erpEnabled' => $erpEnabled,
            'templates' => $erpEnabled && class_exists(DocumentTemplate::class) && Schema::hasTable('document_templates') ? DocumentTemplate::where('is_active', true)->orderBy('name')->get() : collect(),
            'suppliers' => $erpEnabled && class_exists(Supplier::class) && Schema::hasTable('suppliers') ? Supplier::orderBy('name')->get() : collect(),
            'purchaseOrders' => $erpEnabled && class_exists(PurchaseOrder::class) && Schema::hasTable('purchase_orders') ? PurchaseOrder::with('supplier')->where('project_id', $project->id)->get() : collect(),
        ]);
    }

    public function edit(Project $project)
    {
        abort_unless(Client::supportsCompanyStructure(), 404);

        return view('projects.form', $this->formData($project));
    }

    public function update(Request $request, Project $project)
    {
        abort_unless(Client::supportsCompanyStructure(), 404);

        $project->update($this->validated($request));
        return redirect()->route('projects.show', $project)->with('status', 'Project updated.');
    }

    public function destroy(Project $project)
    {
        abort_unless(Client::supportsCompanyStructure(), 404);

        $project->delete();
        return redirect()->route('projects.index')->with('status', 'Project deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'client_id' => ['required', Rule::exists('clients', 'id')->where('business_id', ActiveBusiness::id())],
            'site_id' => ['nullable', Rule::exists('sites', 'id')->where('business_id', ActiveBusiness::id())],
            'contact_id' => ['nullable', Rule::exists('contacts', 'id')->where('business_id', ActiveBusiness::id())],
            'project_name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(Project::STATUSES)],
            'scope' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function formData(Project $project): array
    {
        return [
            'project' => $project,
            'clients' => Client::with('sites', 'contacts')->orderBy('name')->get(),
            'statuses' => Project::STATUSES,
        ];
    }
}
