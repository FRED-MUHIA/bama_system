<?php

namespace Modules\PrintingBranding\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Signatory;
use App\Models\Supplier;
use App\Models\TermsCondition;
use App\Models\User;
use App\Support\ActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Modules\PrintingBranding\Models\Artwork;
use Modules\PrintingBranding\Models\Dispatch;
use Modules\PrintingBranding\Models\Estimate;
use Modules\PrintingBranding\Models\FinishingOption;
use Modules\PrintingBranding\Models\Machine;
use Modules\PrintingBranding\Models\MachineMaintenance;
use Modules\PrintingBranding\Models\Material;
use Modules\PrintingBranding\Models\MaterialReservation;
use Modules\PrintingBranding\Models\OutsourcingOrder;
use Modules\PrintingBranding\Models\PricingRule;
use Modules\PrintingBranding\Models\PrintingClientProfile;
use Modules\PrintingBranding\Models\PrintMethod;
use Modules\PrintingBranding\Models\ProductionJob;
use Modules\PrintingBranding\Models\ProductionOperation;
use Modules\PrintingBranding\Models\ProductionSchedule;
use Modules\PrintingBranding\Models\ProductTemplate;
use Modules\PrintingBranding\Models\ProofApproval;
use Modules\PrintingBranding\Models\QualityCheck;
use Modules\PrintingBranding\Models\Waste;
use Modules\PrintingBranding\Services\ArtworkService;
use Modules\PrintingBranding\Services\DispatchService;
use Modules\PrintingBranding\Services\EstimateService;
use Modules\PrintingBranding\Services\MachineService;
use Modules\PrintingBranding\Services\PrintCostingService;
use Modules\PrintingBranding\Services\PrintingDashboardService;
use Modules\PrintingBranding\Services\PrintingFeatureGate;
use Modules\PrintingBranding\Services\PrintingNumberService;
use Modules\PrintingBranding\Services\PrintingReportingService;
use Modules\PrintingBranding\Services\ProductionJobService;
use Modules\PrintingBranding\Services\ProductionSchedulingService;
use Modules\PrintingBranding\Services\ProofApprovalService;
use Modules\PrintingBranding\Services\QualityControlService;
use Modules\PrintingBranding\Services\WasteService;

class PrintingOperationsController extends Controller
{
    public function estimates(PrintingFeatureGate $gate)
    {
        $gate->authorize('estimates.view');

        return $this->view('Estimating', 'Print estimates, pricing rules, configurable products, and quotation conversion.', [
            'section' => 'estimates',
            'estimates' => Estimate::with('client', 'quotation')->latest()->paginate(20),
            'clients' => Client::orderBy('name')->limit(100)->get(),
            'products' => Product::orderBy('name')->limit(100)->get(),
            'templates' => ProductTemplate::where('is_active', true)->orderBy('name')->get(),
            'methods' => PrintMethod::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function storeEstimate(Request $request, EstimateService $service, PrintingFeatureGate $gate)
    {
        $gate->authorize('estimates.create');
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'product_id' => ['nullable', 'exists:products,id'],
            'product_template_id' => ['nullable', 'exists:printing_product_templates,id'],
            'print_method_id' => ['nullable', 'exists:printing_print_methods,id'],
            'product_name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:1'],
            'specifications' => ['nullable', 'array'],
            'finishing' => ['nullable', 'array'],
            'artwork_charges' => ['nullable', 'numeric', 'min:0'],
            'setup_charges' => ['nullable', 'numeric', 'min:0'],
            'machine_cost' => ['nullable', 'numeric', 'min:0'],
            'labor_cost' => ['nullable', 'numeric', 'min:0'],
            'material_cost' => ['nullable', 'numeric', 'min:0'],
            'outsourcing_cost' => ['nullable', 'numeric', 'min:0'],
            'delivery_cost' => ['nullable', 'numeric', 'min:0'],
            'markup' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:80'],
        ]);

        $estimate = $service->create($this->withoutNullValues($data));

        return redirect()->route('printing-branding.estimates')->with('success', 'Estimate '.$estimate->estimate_number.' created.');
    }

    public function convertEstimate(Estimate $estimate, EstimateService $service, PrintingFeatureGate $gate)
    {
        $gate->authorize('estimates.approve');
        $quotation = $service->convertToQuotation($estimate);

        return redirect()->route('quotations.show', $quotation)->with('success', 'Estimate converted to quotation.');
    }

    public function jobs(PrintingFeatureGate $gate)
    {
        $gate->authorize('production_jobs.view');

        return $this->view('Production Jobs', 'Approved quotations become tenant-scoped production jobs with tickets, materials, stages, and costing.', [
            'section' => 'jobs',
            'jobs' => ProductionJob::with('client', 'quotation', 'ticket', 'machine', 'cost')->latest()->paginate(20),
            'clients' => Client::orderBy('name')->limit(100)->get(),
            'quotations' => Quotation::with('client')->latest()->limit(80)->get(),
            'estimates' => Estimate::latest()->limit(80)->get(),
            'machines' => Machine::orderBy('name')->get(),
            'materials' => Material::where('status', 'Active')->orderBy('name')->get(),
            'clientTypes' => $this->clientTypes(),
        ]);
    }

    public function storeClient(Request $request, PrintingFeatureGate $gate)
    {
        $gate->authorize('production_jobs.create');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'client_type' => ['required', Rule::in($this->clientTypes())],
            'lead_source' => ['nullable', 'string', 'max:120'],
            'print_frequency' => ['nullable', 'string', 'max:120'],
            'price_tier' => ['nullable', 'string', 'max:80'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'client_notes' => ['nullable', 'string'],
        ]);

        $client = Client::create([
            'type' => $data['client_type'] === 'Individual' ? 'individual' : 'company',
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'company_name' => $data['company_name'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['client_notes'] ?? null,
        ]);

        PrintingClientProfile::updateOrCreate(
            ['client_id' => $client->id],
            [
                'client_type' => $data['client_type'],
                'lead_source' => $data['lead_source'] ?? null,
                'print_frequency' => $data['print_frequency'] ?? null,
                'price_tier' => $data['price_tier'] ?? 'Standard',
                'credit_limit' => $data['credit_limit'] ?? 0,
                'client_notes' => $data['client_notes'] ?? null,
                'pipeline_stage' => 'Lead',
            ]
        );

        return redirect()->route('printing-branding.jobs')->with('success', 'Client '.$client->name.' added. You can now select them for a production job.');
    }

    public function storeJob(Request $request, ProductionJobService $service, PrintingFeatureGate $gate)
    {
        $gate->authorize('production_jobs.create');
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'quotation_id' => ['nullable', 'exists:quotations,id'],
            'estimate_id' => ['nullable', 'exists:printing_estimates,id'],
            'product_id' => ['nullable', 'exists:products,id'],
            'machine_id' => ['nullable', 'exists:printing_machines,id'],
            'product_name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:1'],
            'specifications' => ['nullable', 'array'],
            'delivery_date' => ['nullable', 'date'],
            'priority' => ['nullable', 'string', 'max:80'],
            'materials_required' => ['nullable', 'array'],
            'production_notes' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in($this->jobStatuses())],
        ]);
        $data['specifications'] = $this->normalizeJobSpecifications($data);

        $job = $service->create($this->withoutNullValues($data));

        return redirect()->route('printing-branding.jobs')->with('success', 'Production job '.$job->job_number.' created.');
    }

    public function jobFromQuotation(Quotation $quotation, ProductionJobService $service, PrintingFeatureGate $gate)
    {
        $gate->authorize('production_jobs.create');
        $job = $service->fromQuotation($quotation);

        return redirect()->route('printing-branding.jobs')->with('success', 'Production job '.$job->job_number.' created from quotation.');
    }

    public function updateJobStatus(Request $request, ProductionJob $job, ProductionJobService $service, PrintingFeatureGate $gate)
    {
        $gate->authorize('production_jobs.update');
        $data = $request->validate(['status' => ['required', Rule::in($this->jobStatuses())]]);
        $service->updateStatus($job, $data['status']);

        return back()->with('success', 'Job status updated.');
    }

    public function board(PrintingDashboardService $dashboard, PrintingFeatureGate $gate)
    {
        $gate->authorize('production_jobs.view');

        return $this->view('Production Board', 'Visual production board grouped by print workflow status.', [
            'section' => 'board',
            'board' => $dashboard->board(),
        ]);
    }

    public function tickets(PrintingFeatureGate $gate)
    {
        $gate->authorize('production_jobs.view');

        return $this->view('Electronic Job Tickets', 'Printable QR/barcode job tickets for production staff.', [
            'section' => 'tickets',
            'jobs' => ProductionJob::with('client', 'ticket', 'machine')->latest()->paginate(20),
        ]);
    }

    public function ticket(ProductionJob $job, PrintingFeatureGate $gate)
    {
        $gate->authorize('production_jobs.view');

        return view('printing-branding.ticket', ['job' => $job->load('client', 'ticket', 'machine')]);
    }

    public function artwork(PrintingFeatureGate $gate)
    {
        $gate->authorize('artwork.view');

        return $this->view('Artwork Management', 'Artwork files, archived versions, proofs, and client approvals.', [
            'section' => 'artwork',
            'artworks' => Artwork::with('client', 'job', 'designer')->latest()->paginate(20),
            'jobs' => ProductionJob::with('client')->latest()->limit(100)->get(),
            'designers' => $this->activeBusinessUsers()->orderBy('name')->get(),
        ]);
    }

    public function storeArtwork(Request $request, ArtworkService $service, PrintingFeatureGate $gate)
    {
        $gate->authorize('artwork.manage');
        $data = $request->validate([
            'job_id' => ['required', 'exists:printing_jobs,id'],
            'designer_id' => ['nullable', $this->activeBusinessUserExistsRule()],
            'file' => ['nullable', 'file', 'max:10240'],
            'revision_notes' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:80'],
        ]);
        $job = ProductionJob::findOrFail($data['job_id']);
        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('printing/artwork', 'public');
        }
        unset($data['job_id'], $data['file']);

        $artwork = $service->uploadVersion($job, $data);

        return back()->with('success', 'Artwork '.$artwork->artwork_number.' v'.$artwork->version.' uploaded.');
    }

    public function sendProof(Artwork $artwork, ProofApprovalService $service, PrintingFeatureGate $gate)
    {
        $gate->authorize('artwork.manage');
        $service->sendToClient($artwork);

        return back()->with('success', 'Proof sent to client.');
    }

    public function approveProof(Request $request, ProofApproval $approval, ProofApprovalService $service, PrintingFeatureGate $gate)
    {
        $gate->authorize('artwork.approve');
        $data = $request->validate([
            'decision' => ['required', 'in:approve,revision'],
            'notes' => ['nullable', 'string'],
        ]);
        $service->decide($approval, $data['decision'], $data['notes'] ?? null);

        return back()->with('success', 'Proof decision recorded.');
    }

    public function production(PrintingFeatureGate $gate)
    {
        $gate->authorize('production.execute');

        return $this->view('Production', 'Stages, machine work, finishing, quality checkpoints, reprints, and mobile execution.', [
            'section' => 'production',
            'operations' => ProductionOperation::with('job')->latest()->paginate(20),
            'jobs' => ProductionJob::latest()->limit(100)->get(),
            'machines' => Machine::orderBy('name')->get(),
            'staff' => $this->activeBusinessUsers()->orderBy('name')->get(),
        ]);
    }

    public function storeOperation(Request $request, PrintingFeatureGate $gate)
    {
        $gate->authorize('production.execute');
        $data = $request->validate([
            'job_id' => ['required', 'exists:printing_jobs,id'],
            'operator_id' => ['nullable', $this->activeBusinessUserExistsRule()],
            'machine_id' => ['nullable', 'exists:printing_machines,id'],
            'stage' => ['required', 'string', 'max:120'],
            'quantity_produced' => ['nullable', 'numeric', 'min:0'],
            'quantity_rejected' => ['nullable', 'numeric', 'min:0'],
            'material_used' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:80'],
        ]);
        ProductionOperation::create($this->withoutNullValues($data) + ['status' => $data['status'] ?? 'Pending']);

        return back()->with('success', 'Production stage recorded.');
    }

    public function updateOperation(Request $request, ProductionOperation $operation, PrintingFeatureGate $gate)
    {
        $gate->authorize('production.execute');
        $data = $request->validate(['action' => ['required', 'in:start,pause,complete']]);
        $operation->update(match ($data['action']) {
            'start' => ['status' => 'Started', 'started_at' => now()],
            'pause' => ['status' => 'Paused', 'paused_at' => now()],
            'complete' => ['status' => 'Completed', 'completed_at' => now()],
        });

        return back()->with('success', 'Production stage updated.');
    }

    public function materials(PrintingFeatureGate $gate)
    {
        $gate->authorize('inventory.view');

        return $this->view('Materials', 'Printing materials, reservations, stock warnings, and actual consumption.', [
            'section' => 'materials',
            'materials' => Material::with('product', 'supplier')->latest()->paginate(20),
            'reservations' => MaterialReservation::with('job', 'material')->latest()->limit(30)->get(),
            'products' => Product::orderBy('name')->limit(100)->get(),
            'suppliers' => Supplier::orderBy('name')->limit(100)->get(),
        ]);
    }

    public function storeMaterial(Request $request, PrintingFeatureGate $gate)
    {
        $gate->authorize('printing_settings.manage');
        $data = $request->validate([
            'product_id' => ['nullable', 'exists:products,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'material_code' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:120'],
            'unit' => ['nullable', 'string', 'max:20'],
            'gsm' => ['nullable', 'string', 'max:40'],
            'width' => ['nullable', 'numeric', 'min:0'],
            'length' => ['nullable', 'numeric', 'min:0'],
            'color' => ['nullable', 'string', 'max:80'],
            'batch_number' => ['nullable', 'string', 'max:120'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'numeric', 'min:0'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'stock_type' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', 'string', 'max:80'],
        ]);
        Material::create($this->withoutNullValues($data));

        return back()->with('success', 'Material saved.');
    }

    public function consumeMaterial(Request $request, MaterialReservation $reservation, ProductionJobService $service, PrintingFeatureGate $gate)
    {
        $gate->authorize('inventory.consume');
        $data = $request->validate(['quantity' => ['required', 'numeric', 'min:0.001']]);
        $service->consumeMaterial($reservation, (float) $data['quantity']);

        return back()->with('success', 'Material consumed.');
    }

    public function schedule(PrintingFeatureGate $gate)
    {
        $gate->authorize('production.schedule');

        return $this->view('Production Schedule', 'Calendar planning by machine, staff, deadline, priority, and estimated production time.', [
            'section' => 'schedule',
            'schedules' => ProductionSchedule::with('job')->latest()->paginate(20),
            'jobs' => ProductionJob::latest()->limit(100)->get(),
            'machines' => Machine::orderBy('name')->get(),
            'staff' => $this->activeBusinessUsers()->orderBy('name')->get(),
        ]);
    }

    public function storeSchedule(Request $request, ProductionSchedulingService $service, PrintingFeatureGate $gate)
    {
        $gate->authorize('production.schedule');
        $data = $request->validate([
            'job_id' => ['required', 'exists:printing_jobs,id'],
            'machine_id' => ['nullable', 'exists:printing_machines,id'],
            'staff_id' => ['nullable', $this->activeBusinessUserExistsRule()],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'view_type' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', 'string', 'max:80'],
        ]);
        $job = ProductionJob::findOrFail($data['job_id']);
        unset($data['job_id']);
        $service->schedule($job, $data);

        return back()->with('success', 'Production schedule saved.');
    }

    public function machines(PrintingFeatureGate $gate)
    {
        $gate->authorize('machines.manage');

        return $this->view('Machines', 'Machine registry, status, capacity, cost per hour, and maintenance.', [
            'section' => 'machines',
            'machines' => Machine::with('maintenance')->latest()->paginate(20),
            'technicians' => $this->activeBusinessUsers()->orderBy('name')->get(),
        ]);
    }

    public function storeMachine(Request $request, MachineService $service, PrintingFeatureGate $gate)
    {
        $gate->authorize('machines.manage');
        $data = $request->validate([
            'machine_code' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:255'],
            'machine_type' => ['required', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'serial_number' => ['nullable', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:120'],
            'capacity' => ['nullable', 'string', 'max:120'],
            'cost_per_hour' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:80'],
        ]);
        $service->create($this->withoutNullValues($data));

        return back()->with('success', 'Machine saved.');
    }

    public function storeMaintenance(Request $request, Machine $machine, MachineService $service, PrintingFeatureGate $gate)
    {
        $gate->authorize('machines.manage');
        $data = $request->validate([
            'technician_id' => ['nullable', $this->activeBusinessUserExistsRule()],
            'maintenance_type' => ['required', 'string', 'max:120'],
            'service_date' => ['required', 'date'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'parts_used' => ['nullable', 'array'],
            'next_service_date' => ['nullable', 'date'],
            'downtime_minutes' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['maintenance_number'] = app(PrintingNumberService::class)->next('MNT', MachineMaintenance::class, 'maintenance_number');
        $service->recordMaintenance($machine, $this->withoutNullValues($data));

        return back()->with('success', 'Machine maintenance recorded.');
    }

    public function quality(Request $request, QualityControlService $service, PrintingFeatureGate $gate)
    {
        $gate->authorize('production.quality_control');

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'job_id' => ['required', 'exists:printing_jobs,id'],
                'inspector_id' => ['nullable', $this->activeBusinessUserExistsRule()],
                'checkpoints' => ['nullable', 'array'],
                'result' => ['required', 'in:Pass,Conditional Pass,Reject,Reprint Required'],
                'notes' => ['nullable', 'string'],
                'photos' => ['nullable', 'array'],
                'rejected_quantity' => ['nullable', 'numeric', 'min:0'],
                'reason' => ['nullable', 'string', 'max:255'],
            ]);
            $job = ProductionJob::findOrFail($data['job_id']);
            unset($data['job_id']);
            $service->inspect($job, $this->withoutNullValues($data));

            return back()->with('success', 'Quality check saved.');
        }

        return $this->view('Quality Control', 'Print color, alignment, size, material, artwork accuracy, quantity, finishing, and packaging checks.', [
            'section' => 'quality',
            'checks' => QualityCheck::with('job')->latest()->paginate(20),
            'jobs' => ProductionJob::latest()->limit(100)->get(),
            'inspectors' => $this->activeBusinessUsers()->orderBy('name')->get(),
        ]);
    }

    public function waste(Request $request, WasteService $service, PrintingFeatureGate $gate)
    {
        $gate->authorize('production.execute');

        if ($request->isMethod('post')) {
            $service->record($this->withoutNullValues($request->validate([
                'job_id' => ['nullable', 'exists:printing_jobs,id'],
                'material_id' => ['nullable', 'exists:printing_materials,id'],
                'employee_id' => ['nullable', $this->activeBusinessUserExistsRule()],
                'machine_id' => ['nullable', 'exists:printing_machines,id'],
                'waste_type' => ['required', 'string', 'max:120'],
                'quantity' => ['required', 'numeric', 'min:0'],
                'cost' => ['nullable', 'numeric', 'min:0'],
                'reason' => ['nullable', 'string', 'max:255'],
            ])));

            return back()->with('success', 'Waste recorded.');
        }

        return $this->view('Waste Tracking', 'Waste percentage, waste cost, and waste by department or machine.', [
            'section' => 'waste',
            'wastes' => Waste::with('job')->latest()->paginate(20),
            'jobs' => ProductionJob::latest()->limit(100)->get(),
            'materials' => Material::orderBy('name')->get(),
            'machines' => Machine::orderBy('name')->get(),
            'employees' => $this->activeBusinessUsers()->orderBy('name')->get(),
        ]);
    }

    public function dispatch(PrintingFeatureGate $gate)
    {
        $gate->authorize('dispatch.manage');

        return $this->view('Dispatch', 'Packing, delivery, collection, failed delivery, POD, signatures, and delivery notes.', [
            'section' => 'dispatch',
            'dispatches' => Dispatch::with('job')->latest()->paginate(20),
            'jobs' => ProductionJob::with('client')->latest()->limit(100)->get(),
            'drivers' => $this->activeBusinessUsers()->orderBy('name')->get(),
        ]);
    }

    public function storeDispatch(Request $request, DispatchService $service, PrintingFeatureGate $gate)
    {
        $gate->authorize('dispatch.manage');
        $data = $request->validate([
            'job_id' => ['required', 'exists:printing_jobs,id'],
            'driver_id' => ['nullable', $this->activeBusinessUserExistsRule()],
            'status' => ['nullable', 'string', 'max:80'],
            'delivery_address' => ['nullable', 'string'],
            'vehicle' => ['nullable', 'string', 'max:120'],
            'courier' => ['nullable', 'string', 'max:120'],
            'dispatch_date' => ['nullable', 'date'],
            'delivery_date' => ['nullable', 'date'],
            'receiver_name' => ['nullable', 'string', 'max:255'],
        ]);
        $job = ProductionJob::findOrFail($data['job_id']);
        unset($data['job_id']);
        $dispatch = $service->dispatch($job, $this->withoutNullValues($data));

        return back()->with('success', 'Dispatch '.$dispatch->dispatch_number.' saved.');
    }

    public function deliveryNote(Dispatch $dispatch, DispatchService $service, PrintingFeatureGate $gate)
    {
        $gate->authorize('dispatch.manage');
        $note = $service->deliveryNote($dispatch);

        return back()->with('success', 'Delivery note '.$note->delivery_note_number.' generated.');
    }

    public function invoiceJob(ProductionJob $job, Request $request, ProductionJobService $service, PrintingFeatureGate $gate)
    {
        $gate->authorize('job_costing.view');
        $invoice = $service->invoice($job, $request->input('type', 'Final Invoice'));

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice created from production job.');
    }

    public function costing(PrintingFeatureGate $gate)
    {
        $gate->authorize('job_costing.view');

        return $this->view('Job Costing', 'Estimated vs actual cost, margins, variance, and profitability by job.', [
            'section' => 'costing',
            'jobs' => ProductionJob::with('client', 'cost', 'estimate', 'quotation')->latest()->paginate(20),
        ]);
    }

    public function calculateCost(ProductionJob $job, Request $request, PrintCostingService $service, PrintingFeatureGate $gate)
    {
        $gate->authorize('job_costing.manage');
        $service->calculate($job, $request->validate([
            'estimated_material_cost' => ['nullable', 'numeric', 'min:0'],
            'actual_material_cost' => ['nullable', 'numeric', 'min:0'],
            'machine_cost' => ['nullable', 'numeric', 'min:0'],
            'labor_cost' => ['nullable', 'numeric', 'min:0'],
            'artwork_cost' => ['nullable', 'numeric', 'min:0'],
            'finishing_cost' => ['nullable', 'numeric', 'min:0'],
            'outsourcing_cost' => ['nullable', 'numeric', 'min:0'],
            'transport_cost' => ['nullable', 'numeric', 'min:0'],
            'overhead_allocation' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
        ]));

        return back()->with('success', 'Job costing calculated.');
    }

    public function outsourcing(PrintingFeatureGate $gate)
    {
        $gate->authorize('production_jobs.view');

        return $this->view('Outsourcing', 'Outsourced print, embroidery, fabrication, binding, laser cutting, and UV work.', [
            'section' => 'outsourcing',
            'orders' => OutsourcingOrder::with('job')->latest()->paginate(20),
            'jobs' => ProductionJob::latest()->limit(100)->get(),
            'vendors' => Supplier::orderBy('name')->limit(100)->get(),
        ]);
    }

    public function storeOutsourcing(Request $request, PrintingFeatureGate $gate)
    {
        $gate->authorize('production_jobs.update');
        OutsourcingOrder::create($this->withoutNullValues($request->validate([
            'vendor_id' => ['nullable', 'exists:suppliers,id'],
            'job_id' => ['required', 'exists:printing_jobs,id'],
            'service' => ['required', 'string', 'max:160'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'expected_completion' => ['nullable', 'date'],
            'delivery_status' => ['nullable', 'string', 'max:80'],
            'quality_status' => ['nullable', 'string', 'max:80'],
        ])));

        return back()->with('success', 'Outsourcing order saved.');
    }

    public function reports(Request $request, PrintingReportingService $reports, PrintingFeatureGate $gate)
    {
        $gate->authorize('printing_reports.view');
        $date = $request->validate(['date' => ['nullable', 'date']])['date'] ?? null;

        return $this->view('Reports', 'Printing & Branding sales, production, cost, inventory, machine, and staff reports.', [
            'section' => 'reports',
            'reports' => $reports->reports(),
            'dailyProduction' => $reports->dailyProduction($date),
        ]);
    }

    public function storeTemplate(Request $request, PrintingFeatureGate $gate)
    {
        $gate->authorize('printing_settings.manage');
        ProductTemplate::create($this->withoutNullValues($request->validate([
            'template_code' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:120'],
            'specifications' => ['nullable', 'array'],
            'default_costing' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ])) + ['is_active' => $request->boolean('is_active', true)]);

        return back()->with('success', 'Product template saved.');
    }

    public function storePrintMethod(Request $request, PrintingFeatureGate $gate)
    {
        $gate->authorize('printing_settings.manage');
        PrintMethod::create($this->withoutNullValues($request->validate([
            'method_code' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:255'],
            'machine_id' => ['nullable', 'exists:printing_machines,id'],
            'setup_cost' => ['nullable', 'numeric', 'min:0'],
            'minimum_quantity' => ['nullable', 'integer', 'min:1'],
            'estimated_production_minutes' => ['nullable', 'integer', 'min:0'],
            'costing_rules' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ])) + ['is_active' => $request->boolean('is_active', true)]);

        return back()->with('success', 'Print method saved.');
    }

    public function storeFinishingOption(Request $request, PrintingFeatureGate $gate)
    {
        $gate->authorize('printing_settings.manage');
        FinishingOption::create($this->withoutNullValues($request->validate([
            'option_code' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:255'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'production_minutes' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ])) + ['is_active' => $request->boolean('is_active', true)]);

        return back()->with('success', 'Finishing option saved.');
    }

    public function storePricingRule(Request $request, PrintingFeatureGate $gate)
    {
        $gate->authorize('printing_settings.manage');
        PricingRule::create($this->withoutNullValues($request->validate([
            'rule_code' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:255'],
            'rule_type' => ['required', 'string', 'max:120'],
            'client_tier' => ['nullable', 'string', 'max:80'],
            'product_category' => ['nullable', 'string', 'max:120'],
            'rate' => ['nullable', 'numeric', 'min:0'],
            'conditions' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ])) + ['is_active' => $request->boolean('is_active', true)]);

        return back()->with('success', 'Pricing rule saved.');
    }

    public function reportExport(Request $request, string $type, PrintingReportingService $reports, PrintingFeatureGate $gate)
    {
        $gate->authorize('printing_reports.view');
        $date = $request->validate(['date' => ['nullable', 'date']])['date'] ?? null;

        return $reports->export($type, $date);
    }

    public function settings(PrintingFeatureGate $gate)
    {
        $gate->authorize('printing_settings.manage');

        return $this->view('Settings', 'Configure print products, materials, machines, methods, finishing, pricing, statuses, stages, templates, notifications, and workflows.', [
            'section' => 'settings',
            'templates' => ProductTemplate::latest()->paginate(20),
            'methods' => PrintMethod::latest()->get(),
            'finishing' => FinishingOption::latest()->get(),
            'pricingRules' => PricingRule::latest()->get(),
            'machines' => Machine::latest()->limit(20)->get(),
            'companySettings' => $this->activeCompanySettings(),
            'paymentMethods' => Schema::hasTable('payment_methods') ? PaymentMethod::where('is_active', true)->latest()->get() : collect(),
            'terms' => Schema::hasTable('terms_conditions') ? TermsCondition::latest()->get() : collect(),
            'signatories' => Schema::hasTable('signatories') ? Signatory::where('is_active', true)->orderBy('name')->get() : collect(),
        ]);
    }

    public function mobileJob(ProductionJob $job, PrintingFeatureGate $gate)
    {
        $gate->authorize('production.execute');

        return view('printing-branding.mobile-job', ['job' => $job->load('client', 'ticket', 'artworks')]);
    }

    public function reorder(ProductionJob $job, ProductionJobService $service, PrintingFeatureGate $gate)
    {
        $gate->authorize('production_jobs.create');
        $new = $service->create([
            'client_id' => $job->client_id,
            'product_id' => $job->product_id,
            'product_name' => $job->product_name,
            'quantity' => $job->quantity,
            'specifications' => $job->specifications,
            'artwork_path' => $job->artwork_path,
            'delivery_date' => now()->addDays(7)->toDateString(),
            'priority' => $job->priority,
            'materials_required' => $job->materials_required,
            'production_notes' => 'Reorder from '.$job->job_number,
            'status' => 'Draft',
        ]);

        return redirect()->route('printing-branding.jobs')->with('success', 'Reorder created as '.$new->job_number.'.');
    }

    private function view(string $title, string $description, array $data = [])
    {
        return view('printing-branding.operations', $data + compact('title', 'description'));
    }

    private function activeCompanySettings(): CompanySetting
    {
        $defaults = ['company_name' => ActiveBusiness::current()?->name ?? 'BAMA'];

        foreach ([
            'primary_color' => CompanySetting::DEFAULT_PRIMARY_COLOR,
            'secondary_color' => CompanySetting::DEFAULT_SECONDARY_COLOR,
            'accent_color' => CompanySetting::DEFAULT_ACCENT_COLOR,
        ] as $colorColumn => $default) {
            if (Schema::hasColumn('company_settings', $colorColumn)) {
                $defaults[$colorColumn] = $default;
            }
        }

        return CompanySetting::firstOrCreate(
            ['business_id' => ActiveBusiness::id()],
            $defaults,
        );
    }

    private function jobStatuses(): array
    {
        return ['Draft', 'Awaiting Artwork', 'Artwork In Progress', 'Awaiting Approval', 'Approved', 'Queued', 'In Production', 'Printing', 'Finishing', 'Quality Control', 'Ready for Dispatch', 'Dispatched', 'Completed', 'On Hold', 'Cancelled'];
    }

    private function normalizeJobSpecifications(array $data): array
    {
        $specifications = collect($data['specifications'] ?? [])
            ->filter(fn ($value) => filled($value))
            ->all();

        $specifications['Quantity'] = (string) $data['quantity'];

        return $specifications;
    }

    private function clientTypes(): array
    {
        return ['Individual', 'SME', 'Corporate', 'Government', 'NGO', 'School', 'Hotel', 'Event Company', 'Reseller', 'Agency'];
    }

    private function withoutNullValues(array $data): array
    {
        return collect($data)
            ->reject(fn ($value) => is_null($value))
            ->map(fn ($value) => is_array($value) ? $this->withoutNullValues($value) : $value)
            ->all();
    }
}
