<?php

namespace Modules\Construction\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Project;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Construction\Models\ConstructionBoq;
use Modules\Construction\Models\ConstructionBoqItem;
use Modules\Construction\Models\ConstructionCertificate;
use Modules\Construction\Models\ConstructionContractor;
use Modules\Construction\Models\ConstructionDefect;
use Modules\Construction\Models\ConstructionEquipment;
use Modules\Construction\Models\ConstructionEstimate;
use Modules\Construction\Models\ConstructionHandover;
use Modules\Construction\Models\ConstructionMaterial;
use Modules\Construction\Models\ConstructionMaterialConsumption;
use Modules\Construction\Models\ConstructionMaterialRequest;
use Modules\Construction\Models\ConstructionProgressMeasurement;
use Modules\Construction\Models\ConstructionProjectProfile;
use Modules\Construction\Models\ConstructionQualityInspection;
use Modules\Construction\Models\ConstructionRfi;
use Modules\Construction\Models\ConstructionSafetyIncident;
use Modules\Construction\Models\ConstructionSite;
use Modules\Construction\Models\ConstructionSiteDiary;
use Modules\Construction\Models\ConstructionSiteInstruction;
use Modules\Construction\Models\ConstructionSiteReport;
use Modules\Construction\Models\ConstructionSubcontract;
use Modules\Construction\Models\ConstructionTender;
use Modules\Construction\Models\ConstructionVariation;
use Modules\Construction\Services\BOQService;
use Modules\Construction\Services\CommercialService;
use Modules\Construction\Services\ConstructionDashboardService;
use Modules\Construction\Services\ConstructionEstimateService;
use Modules\Construction\Services\ConstructionFeatureGate;
use Modules\Construction\Services\ConstructionReportingService;
use Modules\Construction\Services\ConstructionService;
use Modules\Construction\Services\ContractorService;
use Modules\Construction\Services\HandoverService;
use Modules\Construction\Services\MaterialManagementService;
use Modules\Construction\Services\QualitySafetyService;
use Modules\Construction\Services\SiteManagementService;
use Modules\Construction\Services\TenderService;

class ConstructionOperationsController extends Controller
{
    public function projects(ConstructionFeatureGate $gate)
    {
        $gate->authorize('projects.manage');

        return $this->view('Projects', 'Project is the central construction record connecting clients, BOQ, sites, materials, commercial controls, quality, safety, and handover.', [
            'section' => 'projects',
            'profiles' => ConstructionProjectProfile::with('project', 'client', 'projectManager', 'siteManager')->latest()->paginate(20),
        ]);
    }

    public function storeProject(Request $request, ConstructionService $service, ConstructionFeatureGate $gate)
    {
        $gate->authorize('projects.manage');
        $profile = $service->createProject($request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'project_name' => ['required', 'string', 'max:255'],
            'contract_type' => ['nullable', 'string', 'max:120'],
            'contract_value' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'planned_completion' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'retention_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'defects_liability_days' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in($this->projectStatuses())],
            'scope' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]));

        return redirect()->route('construction.projects')->with('success', 'Project '.$profile->project_number.' created.');
    }

    public function boqs(ConstructionFeatureGate $gate)
    {
        $gate->authorize('boq.view');

        return $this->view('BOQ & Estimating', 'Bill of quantities, rate analysis, estimate creation, and conversion to tenders or quotations.', [
            'section' => 'boqs',
            'boqs' => ConstructionBoq::with('project', 'items.rateComponents')->latest()->paginate(10),
            'estimates' => ConstructionEstimate::with('client', 'project', 'boq')->latest()->paginate(10, ['*'], 'estimate_page'),
            'boqItems' => ConstructionBoqItem::with('boq')->latest()->limit(80)->get(),
        ]);
    }

    public function storeBoq(Request $request, BOQService $service, ConstructionFeatureGate $gate)
    {
        $gate->authorize('boq.create');
        $boq = $service->create($request->validate([
            'project_id' => ['nullable', 'exists:projects,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:80'],
            'preliminaries' => ['nullable', 'numeric', 'min:0'],
            'contingency' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:80'],
        ]));

        return redirect()->route('construction.boqs')->with('success', 'BOQ '.$boq->boq_number.' created.');
    }

    public function storeBoqItem(Request $request, ConstructionBoq $boq, BOQService $service, ConstructionFeatureGate $gate)
    {
        $gate->authorize('boq.update');
        $service->addItem($boq, $request->validate([
            'item_number' => ['nullable', 'string', 'max:80'],
            'description' => ['required', 'string'],
            'unit' => ['required', 'string', 'max:40'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'material_rate' => ['nullable', 'numeric', 'min:0'],
            'labour_rate' => ['nullable', 'numeric', 'min:0'],
            'equipment_rate' => ['nullable', 'numeric', 'min:0'],
            'subcontract_rate' => ['nullable', 'numeric', 'min:0'],
            'unit_rate' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]));

        return redirect()->route('construction.boqs')->with('success', 'BOQ item added and totals recalculated.');
    }

    public function storeRateComponent(Request $request, ConstructionBoqItem $item, BOQService $service, ConstructionFeatureGate $gate)
    {
        $gate->authorize('boq.update');
        $service->addRateComponent($item, $request->validate([
            'component_type' => ['required', Rule::in(['Materials', 'Labour', 'Equipment', 'Subcontract', 'Transport', 'Waste', 'Overheads', 'Profit'])],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:40'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'rate' => ['required', 'numeric', 'min:0'],
        ]));

        return redirect()->route('construction.boqs')->with('success', 'Rate analysis component added.');
    }

    public function storeEstimate(Request $request, ConstructionEstimateService $service, ConstructionFeatureGate $gate)
    {
        $gate->authorize('estimates.manage');
        $estimate = $service->create($request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'boq_id' => ['nullable', 'exists:construction_boqs,id'],
            'title' => ['required', 'string', 'max:255'],
            'direct_cost' => ['required', 'numeric', 'min:0'],
            'overhead_percentage' => ['nullable', 'numeric', 'min:0'],
            'profit_percentage' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:80'],
        ]));

        return redirect()->route('construction.boqs')->with('success', 'Estimate '.$estimate->estimate_number.' created.');
    }

    public function estimateToTender(ConstructionEstimate $estimate, ConstructionEstimateService $service, ConstructionFeatureGate $gate)
    {
        $gate->authorize('tenders.manage');
        $tender = $service->convertToTender($estimate);

        return redirect()->route('construction.tenders')->with('success', 'Tender '.$tender->tender_number.' created from estimate.');
    }

    public function estimateToQuotation(ConstructionEstimate $estimate, ConstructionEstimateService $service, ConstructionFeatureGate $gate)
    {
        $gate->authorize('estimates.manage');
        $quotation = $service->convertToQuotation($estimate);

        return redirect()->route('quotations.show', $quotation)->with('success', 'Construction estimate converted to quotation.');
    }

    public function tenders(ConstructionFeatureGate $gate)
    {
        $gate->authorize('tenders.manage');

        return $this->view('Tenders', 'Tender opportunities, checklist readiness, submitted bids, and award conversion into projects.', [
            'section' => 'tenders',
            'tenders' => ConstructionTender::with('client', 'project', 'checklist')->latest()->paginate(20),
            'boqs' => ConstructionBoq::latest()->limit(80)->get(),
            'estimates' => ConstructionEstimate::latest()->limit(80)->get(),
        ]);
    }

    public function storeTender(Request $request, TenderService $service, ConstructionFeatureGate $gate)
    {
        $gate->authorize('tenders.manage');
        $tender = $service->create($request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'boq_id' => ['nullable', 'exists:construction_boqs,id'],
            'estimate_id' => ['nullable', 'exists:construction_estimates,id'],
            'name' => ['required', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', 'string', 'max:120'],
            'tender_value' => ['nullable', 'numeric', 'min:0'],
            'submission_date' => ['nullable', 'date'],
            'closing_date' => ['nullable', 'date'],
            'tender_bond' => ['nullable', 'numeric', 'min:0'],
            'site_visit_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:80'],
            'requirements' => ['nullable', 'string'],
        ]));

        return redirect()->route('construction.tenders')->with('success', 'Tender '.$tender->tender_number.' created.');
    }

    public function tenderToProject(ConstructionTender $tender, TenderService $service, ConstructionFeatureGate $gate)
    {
        $gate->authorize('projects.manage');
        $profile = $service->convertToProject($tender);

        return redirect()->route('construction.projects')->with('success', 'Awarded tender converted to project '.$profile->project_number.'.');
    }

    public function site(ConstructionFeatureGate $gate)
    {
        $gate->authorize('sites.manage');

        return $this->view('Site', 'Sites, daily reports, diary timeline, site instructions, and RFIs for field execution.', [
            'section' => 'site',
            'sites' => ConstructionSite::with('project')->latest()->paginate(12),
            'dailyReports' => ConstructionSiteReport::with('project', 'site')->latest()->paginate(10, ['*'], 'report_page'),
            'diaries' => ConstructionSiteDiary::with('project', 'site')->latest()->limit(30)->get(),
            'rfis' => ConstructionRfi::with('project')->latest()->limit(30)->get(),
            'instructions' => ConstructionSiteInstruction::with('project')->latest()->limit(30)->get(),
        ]);
    }

    public function storeSite(Request $request, ConstructionService $service, ConstructionFeatureGate $gate)
    {
        $gate->authorize('sites.manage');
        $site = $service->createSite($request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'operating_hours' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'max:80'],
        ]));

        return redirect()->route('construction.site')->with('success', 'Site '.$site->name.' created.');
    }

    public function storeDailyReport(Request $request, SiteManagementService $service, ConstructionFeatureGate $gate)
    {
        $gate->authorize('site_reports.create');
        $report = $service->dailyReport($request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'site_id' => ['nullable', 'exists:construction_sites,id'],
            'report_date' => ['required', 'date'],
            'weather' => ['nullable', 'string', 'max:120'],
            'workforce_count' => ['nullable', 'integer', 'min:0'],
            'work_completed' => ['nullable', 'string'],
            'activities_in_progress' => ['nullable', 'string'],
            'materials_received' => ['nullable', 'string'],
            'materials_used' => ['nullable', 'string'],
            'equipment_used' => ['nullable', 'string'],
            'delays' => ['nullable', 'string'],
            'safety_issues' => ['nullable', 'string'],
            'quality_issues' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'next_day_plan' => ['nullable', 'string'],
        ]) + ['prepared_by' => auth()->id()]);

        return redirect()->route('construction.site')->with('success', 'Daily report '.$report->report_number.' submitted.');
    }

    public function storeDiary(Request $request, SiteManagementService $service, ConstructionFeatureGate $gate)
    {
        $gate->authorize('sites.manage');
        $service->diary($request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'site_id' => ['nullable', 'exists:construction_sites,id'],
            'event_date' => ['required', 'date'],
            'event_time' => ['nullable'],
            'event_type' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string'],
        ]) + ['user_id' => auth()->id()]);

        return redirect()->route('construction.site')->with('success', 'Site diary entry added.');
    }

    public function storeRfi(Request $request, SiteManagementService $service, ConstructionFeatureGate $gate)
    {
        $gate->authorize('rfi.manage');
        $rfi = $service->rfi($request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'site_id' => ['nullable', 'exists:construction_sites,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'question' => ['required', 'string'],
            'drawing_reference' => ['nullable', 'string', 'max:120'],
            'boq_reference' => ['nullable', 'string', 'max:120'],
            'required_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:80'],
        ]) + ['raised_by' => auth()->id()]);

        return redirect()->route('construction.site')->with('success', 'RFI '.$rfi->rfi_number.' raised.');
    }

    public function storeInstruction(Request $request, SiteManagementService $service, ConstructionFeatureGate $gate)
    {
        $gate->authorize('site_instructions.manage');
        $instruction = $service->instruction($request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'site_id' => ['nullable', 'exists:construction_sites,id'],
            'recipient_id' => ['nullable', 'exists:users,id'],
            'instruction' => ['required', 'string'],
            'instruction_date' => ['required', 'date'],
            'priority' => ['nullable', 'string', 'max:80'],
            'due_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:80'],
        ]) + ['issuer_id' => auth()->id()]);

        return redirect()->route('construction.site')->with('success', 'Instruction '.$instruction->instruction_number.' created.');
    }

    public function materials(ConstructionFeatureGate $gate)
    {
        $gate->authorize('materials.manage');

        return $this->view('Materials', 'Construction material catalog, site requests, issues, usage, and variance tracking.', [
            'section' => 'materials',
            'materials' => ConstructionMaterial::with('supplier')->latest()->paginate(15),
            'requests' => ConstructionMaterialRequest::with('project', 'site')->latest()->paginate(10, ['*'], 'request_page'),
            'consumptions' => ConstructionMaterialConsumption::with('project', 'site')->latest()->limit(30)->get(),
        ]);
    }

    public function storeMaterial(Request $request, MaterialManagementService $service, ConstructionFeatureGate $gate)
    {
        $gate->authorize('materials.manage');
        $material = $service->material($request->validate([
            'material_code' => ['nullable', 'string', 'max:120'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:120'],
            'unit' => ['required', 'string', 'max:40'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'numeric', 'min:0'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:80'],
        ]));

        return redirect()->route('construction.materials')->with('success', 'Material '.$material->name.' saved.');
    }

    public function storeMaterialRequest(Request $request, MaterialManagementService $service, ConstructionFeatureGate $gate)
    {
        $gate->authorize('material_requests.create');
        $mr = $service->request($request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'site_id' => ['nullable', 'exists:construction_sites,id'],
            'material_id' => ['nullable', 'exists:construction_materials,id'],
            'material_name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'required_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string'],
        ]) + ['requested_by' => auth()->id()]);

        return redirect()->route('construction.materials')->with('success', 'Material request '.$mr->request_number.' created.');
    }

    public function storeConsumption(Request $request, MaterialManagementService $service, ConstructionFeatureGate $gate)
    {
        $gate->authorize('materials.manage');
        $service->consume($request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'site_id' => ['nullable', 'exists:construction_sites,id'],
            'material_id' => ['nullable', 'exists:construction_materials,id'],
            'material_name' => ['nullable', 'string', 'max:255'],
            'usage_date' => ['required', 'date'],
            'planned_quantity' => ['nullable', 'numeric', 'min:0'],
            'issued_quantity' => ['nullable', 'numeric', 'min:0'],
            'actual_quantity' => ['required', 'numeric', 'min:0'],
            'waste_quantity' => ['nullable', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]) + ['employee_id' => auth()->id()]);

        return redirect()->route('construction.materials')->with('success', 'Material consumption recorded.');
    }

    public function contractors(ConstructionFeatureGate $gate)
    {
        $gate->authorize('contractors.manage');

        return $this->view('Contractors', 'Contractor database, subcontract values, trades, performance, and contract status.', [
            'section' => 'contractors',
            'contractors' => ConstructionContractor::latest()->paginate(12),
            'subcontracts' => ConstructionSubcontract::with('project', 'contractor')->latest()->paginate(12, ['*'], 'subcontract_page'),
        ]);
    }

    public function storeContractor(Request $request, ContractorService $service, ConstructionFeatureGate $gate)
    {
        $gate->authorize('contractors.manage');
        $contractor = $service->contractor($request->validate([
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'company_name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:120'],
            'trade' => ['nullable', 'string', 'max:120'],
            'contact_name' => ['nullable', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:180'],
            'registration_number' => ['nullable', 'string', 'max:120'],
            'tax_pin' => ['nullable', 'string', 'max:120'],
            'performance_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'status' => ['nullable', 'string', 'max:80'],
        ]));

        return redirect()->route('construction.contractors')->with('success', 'Contractor '.$contractor->company_name.' saved.');
    }

    public function storeSubcontract(Request $request, ContractorService $service, ConstructionFeatureGate $gate)
    {
        $gate->authorize('subcontracts.manage');
        $subcontract = $service->subcontract($request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'contractor_id' => ['required', 'exists:construction_contractors,id'],
            'scope' => ['nullable', 'string'],
            'contract_sum' => ['required', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'completion_date' => ['nullable', 'date'],
            'retention_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:80'],
        ]));

        return redirect()->route('construction.contractors')->with('success', 'Subcontract '.$subcontract->subcontract_number.' created.');
    }

    public function commercial(ConstructionFeatureGate $gate)
    {
        $gate->authorize('construction.finance');

        return $this->view('Commercial', 'Measurements, interim payment certificates, variations, retention, and invoice integration.', [
            'section' => 'commercial',
            'measurements' => ConstructionProgressMeasurement::with('project', 'boqItem')->latest()->paginate(10),
            'certificates' => ConstructionCertificate::with('project', 'client', 'invoice')->latest()->paginate(10, ['*'], 'certificate_page'),
            'variations' => ConstructionVariation::with('project', 'client')->latest()->paginate(10, ['*'], 'variation_page'),
            'boqItems' => ConstructionBoqItem::with('boq')->latest()->limit(80)->get(),
        ]);
    }

    public function storeMeasurement(Request $request, CommercialService $service, ConstructionFeatureGate $gate)
    {
        $gate->authorize('measurements.create');
        $measurement = $service->measurement($request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'site_id' => ['nullable', 'exists:construction_sites,id'],
            'boq_item_id' => ['nullable', 'exists:construction_boq_items,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'measured_quantity' => ['required', 'numeric', 'min:0'],
            'measurement_date' => ['required', 'date'],
            'status' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string'],
        ]) + ['measured_by' => auth()->id()]);

        return redirect()->route('construction.commercial')->with('success', 'Measurement '.$measurement->measurement_number.' recorded.');
    }

    public function storeCertificate(Request $request, CommercialService $service, ConstructionFeatureGate $gate)
    {
        $gate->authorize('certificates.create');
        $certificate = $service->certificate($request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'period' => ['nullable', 'string', 'max:120'],
            'contract_value' => ['nullable', 'numeric', 'min:0'],
            'work_executed' => ['nullable', 'numeric', 'min:0'],
            'materials_on_site' => ['nullable', 'numeric', 'min:0'],
            'approved_variations' => ['nullable', 'numeric', 'min:0'],
            'gross_certified' => ['nullable', 'numeric', 'min:0'],
            'retention' => ['nullable', 'numeric', 'min:0'],
            'advance_recovery' => ['nullable', 'numeric', 'min:0'],
            'previous_certificates' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:80'],
        ]));

        return redirect()->route('construction.commercial')->with('success', 'Certificate '.$certificate->certificate_number.' created.');
    }

    public function invoiceCertificate(ConstructionCertificate $certificate, CommercialService $service, ConstructionFeatureGate $gate)
    {
        $gate->authorize('certificates.approve');
        $invoice = $service->invoiceCertificate($certificate);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Construction certificate invoiced.');
    }

    public function storeVariation(Request $request, CommercialService $service, ConstructionFeatureGate $gate)
    {
        $gate->authorize('variations.create');
        $variation = $service->variation($request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'instruction_reference' => ['nullable', 'string', 'max:120'],
            'description' => ['required', 'string'],
            'reason' => ['nullable', 'string', 'max:255'],
            'cost_impact' => ['nullable', 'numeric'],
            'time_impact_days' => ['nullable', 'integer'],
            'submitted_date' => ['nullable', 'date'],
            'approved_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:80'],
        ]));

        return redirect()->route('construction.commercial')->with('success', 'Variation '.$variation->variation_number.' created.');
    }

    public function quality(ConstructionFeatureGate $gate)
    {
        $gate->authorize('quality.manage');

        return $this->view('Quality', 'Inspection requests, quality results, corrective actions, NCR-ready registers, and defects.', [
            'section' => 'quality',
            'inspections' => ConstructionQualityInspection::with('project', 'site')->latest()->paginate(12),
            'defects' => ConstructionDefect::with('project', 'site', 'contractor')->latest()->paginate(12, ['*'], 'defect_page'),
        ]);
    }

    public function storeInspection(Request $request, QualitySafetyService $service, ConstructionFeatureGate $gate)
    {
        $gate->authorize('quality.manage');
        $inspection = $service->inspection($request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'site_id' => ['nullable', 'exists:construction_sites,id'],
            'inspection_type' => ['required', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:255'],
            'activity' => ['nullable', 'string', 'max:255'],
            'inspection_date' => ['required', 'date'],
            'result' => ['required', Rule::in(['Pass', 'Conditional Pass', 'Fail'])],
            'comments' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:80'],
        ]) + ['inspector_id' => auth()->id()]);

        return redirect()->route('construction.quality')->with('success', 'Inspection '.$inspection->inspection_number.' recorded.');
    }

    public function storeDefect(Request $request, QualitySafetyService $service, ConstructionFeatureGate $gate)
    {
        $gate->authorize('defects.manage');
        $defect = $service->defect($request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'site_id' => ['nullable', 'exists:construction_sites,id'],
            'contractor_id' => ['nullable', 'exists:construction_contractors,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'area' => ['nullable', 'string', 'max:120'],
            'description' => ['required', 'string'],
            'reported_date' => ['nullable', 'date'],
            'target_date' => ['nullable', 'date'],
            'severity' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', 'string', 'max:80'],
        ]));

        return redirect()->route('construction.quality')->with('success', 'Defect '.$defect->defect_number.' created.');
    }

    public function safety(ConstructionFeatureGate $gate)
    {
        $gate->authorize('safety.manage');

        return $this->view('Safety', 'Safety incidents, near misses, corrective action, site readiness, and safety performance.', [
            'section' => 'safety',
            'incidents' => ConstructionSafetyIncident::with('project', 'site', 'contractor')->latest()->paginate(20),
        ]);
    }

    public function storeIncident(Request $request, QualitySafetyService $service, ConstructionFeatureGate $gate)
    {
        $gate->authorize('safety.manage');
        $incident = $service->incident($request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'site_id' => ['nullable', 'exists:construction_sites,id'],
            'contractor_id' => ['nullable', 'exists:construction_contractors,id'],
            'incident_date' => ['required', 'date'],
            'incident_time' => ['nullable'],
            'location' => ['nullable', 'string', 'max:255'],
            'incident_type' => ['nullable', 'string', 'max:120'],
            'severity' => ['nullable', 'string', 'max:80'],
            'description' => ['required', 'string'],
            'immediate_action' => ['nullable', 'string'],
            'root_cause' => ['nullable', 'string'],
            'corrective_action' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:80'],
        ]) + ['employee_id' => auth()->id()]);

        return redirect()->route('construction.safety')->with('success', 'Incident '.$incident->incident_number.' logged.');
    }

    public function equipment(ConstructionFeatureGate $gate)
    {
        $gate->authorize('construction.view');

        return $this->view('Equipment', 'Plant, equipment, utilization, hours, fuel, cost, status, and maintenance alerts.', [
            'section' => 'equipment',
            'equipment' => ConstructionEquipment::with('project', 'site', 'operator')->latest()->paginate(20),
        ]);
    }

    public function storeEquipment(Request $request, ConstructionFeatureGate $gate)
    {
        $gate->authorize('construction.view');
        $equipment = ConstructionEquipment::create($request->validate([
            'project_id' => ['nullable', 'exists:projects,id'],
            'site_id' => ['nullable', 'exists:construction_sites,id'],
            'operator_id' => ['nullable', 'exists:users,id'],
            'equipment_code' => ['required', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:255'],
            'equipment_type' => ['nullable', 'string', 'max:120'],
            'hours_used' => ['nullable', 'numeric', 'min:0'],
            'fuel_used' => ['nullable', 'numeric', 'min:0'],
            'cost_per_hour' => ['nullable', 'numeric', 'min:0'],
            'location' => ['nullable', 'string', 'max:255'],
            'next_service_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:80'],
        ]));

        return redirect()->route('construction.equipment')->with('success', 'Equipment '.$equipment->equipment_code.' saved.');
    }

    public function handover(ConstructionFeatureGate $gate)
    {
        $gate->authorize('handover.manage');

        return $this->view('Handover', 'Practical completion, handover checklists, client acceptance, DLP tracking, and closeout controls.', [
            'section' => 'handover',
            'handovers' => ConstructionHandover::with('project')->latest()->paginate(20),
        ]);
    }

    public function storeHandover(Request $request, HandoverService $service, ConstructionFeatureGate $gate)
    {
        $gate->authorize('handover.manage');
        $handover = $service->create($request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'practical_completion_date' => ['nullable', 'date'],
            'handover_date' => ['nullable', 'date'],
            'dlp_start_date' => ['nullable', 'date'],
            'dlp_end_date' => ['nullable', 'date'],
            'client_acceptance' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string'],
        ]) + ['checklist' => $this->handoverChecklist()]);

        return redirect()->route('construction.handover')->with('success', 'Handover '.$handover->handover_number.' created.');
    }

    public function reports(ConstructionReportingService $reports, ConstructionFeatureGate $gate)
    {
        $gate->authorize('construction.reports');

        return $this->view('Reports', 'Construction project, BOQ, cost, materials, procurement, contractor, commercial, site, safety, and quality reports.', [
            'section' => 'reports',
            'summary' => $reports->summary(),
        ]);
    }

    public function reportCsv(string $type, ConstructionReportingService $reports, ConstructionFeatureGate $gate)
    {
        $gate->authorize('construction.reports');

        return $reports->csv($type);
    }

    public function mobile(ConstructionDashboardService $dashboard, ConstructionFeatureGate $gate)
    {
        $gate->authorize('construction.view');

        return $this->view('Mobile Site', 'Fast mobile-ready actions for site teams with minimal typing.', [
            'section' => 'mobile',
            'mobileActions' => $dashboard->mobileActions(),
            'sites' => ConstructionSite::with('project')->latest()->limit(20)->get(),
        ]);
    }

    private function view(string $title, string $description, array $data = [])
    {
        return view('construction.operations', array_merge([
            'title' => $title,
            'description' => $description,
            'clients' => Client::orderBy('name')->limit(120)->get(),
            'projects' => Project::orderByDesc('id')->limit(120)->get(),
            'sitesList' => ConstructionSite::with('project')->orderBy('name')->limit(120)->get(),
            'materialsList' => ConstructionMaterial::orderBy('name')->limit(120)->get(),
            'contractorsList' => ConstructionContractor::orderBy('company_name')->limit(120)->get(),
            'suppliers' => Supplier::orderBy('name')->limit(120)->get(),
            'users' => User::where('role', '!=', 'client_portal')->orderBy('name')->limit(120)->get(),
        ], $data));
    }

    private function projectStatuses(): array
    {
        return ['Tendering', 'Awarded', 'Mobilization', 'Active', 'Suspended', 'Near Completion', 'Practical Completion', 'Defects Liability', 'Final Completion', 'Closed', 'Cancelled'];
    }

    private function handoverChecklist(): array
    {
        return ['As-Built Drawings', 'Operation Manuals', 'Warranties', 'Certificates', 'Keys', 'Equipment Manuals', 'Training Records', 'Final Inspection', 'Client Acceptance'];
    }
}
