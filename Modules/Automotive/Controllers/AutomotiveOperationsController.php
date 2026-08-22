<?php

namespace Modules\Automotive\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Automotive\Models\CheckIn;
use Modules\Automotive\Models\Comeback;
use Modules\Automotive\Models\Complaint;
use Modules\Automotive\Models\CustomerFeedback;
use Modules\Automotive\Models\Estimate;
use Modules\Automotive\Models\Fleet;
use Modules\Automotive\Models\Inspection;
use Modules\Automotive\Models\JobCard;
use Modules\Automotive\Models\LabourOperation;
use Modules\Automotive\Models\LabourTask;
use Modules\Automotive\Models\Part;
use Modules\Automotive\Models\PartRequest;
use Modules\Automotive\Models\PartRequestItem;
use Modules\Automotive\Models\QualityCheck;
use Modules\Automotive\Models\RoadTest;
use Modules\Automotive\Models\ServiceBooking;
use Modules\Automotive\Models\ServicePackage;
use Modules\Automotive\Models\ServiceReminder;
use Modules\Automotive\Models\SpecialtyRecord;
use Modules\Automotive\Models\TestDrive;
use Modules\Automotive\Models\TradeIn;
use Modules\Automotive\Models\Vehicle;
use Modules\Automotive\Models\VehicleRelease;
use Modules\Automotive\Models\VehicleSale;
use Modules\Automotive\Models\Warranty;
use Modules\Automotive\Models\WorkshopBay;
use Modules\Automotive\Services\AutomotiveEstimateService;
use Modules\Automotive\Services\AutomotiveFeatureGate;
use Modules\Automotive\Services\AutomotiveFinanceService;
use Modules\Automotive\Services\AutomotiveInventoryService;
use Modules\Automotive\Services\AutomotiveNumberService;
use Modules\Automotive\Services\AutomotiveReportingService;
use Modules\Automotive\Services\BookingService;
use Modules\Automotive\Services\FleetService;
use Modules\Automotive\Services\InspectionService;
use Modules\Automotive\Services\JobCardService;
use Modules\Automotive\Services\QualityControlService;
use Modules\Automotive\Services\VehicleCheckInService;
use Modules\Automotive\Services\VehicleService;
use Modules\Automotive\Services\WarrantyService;
use Modules\Automotive\Services\WorkshopService;

class AutomotiveOperationsController extends Controller
{
    public function vehicles(AutomotiveFeatureGate $gate)
    {
        $gate->authorize('vehicles.view');

        return $this->view('Vehicles', 'Vehicle profiles, ownership, service intervals, insurance, mileage, and full workshop history.', [
            'section' => 'vehicles',
            'vehicles' => Vehicle::with('client', 'fleet')->latest()->paginate(20),
        ]);
    }

    public function storeVehicle(Request $request, VehicleService $service, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('vehicles.create');
        $vehicle = $service->create($request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'fleet_id' => ['nullable', 'exists:automotive_fleets,id'],
            'registration_number' => ['required', 'string', 'max:80'],
            'vin' => ['nullable', 'string', 'max:120'],
            'engine_number' => ['nullable', 'string', 'max:120'],
            'make' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'variant' => ['nullable', 'string', 'max:120'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'color' => ['nullable', 'string', 'max:80'],
            'fuel_type' => ['nullable', Rule::in(['Petrol', 'Diesel', 'Hybrid', 'Electric', 'LPG', 'CNG', 'Other'])],
            'transmission' => ['nullable', 'string', 'max:80'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'insurance_provider' => ['nullable', 'string', 'max:160'],
            'insurance_policy_number' => ['nullable', 'string', 'max:160'],
            'inspection_expiry' => ['nullable', 'date'],
            'service_interval' => ['nullable', 'integer', 'min:0'],
            'next_service_date' => ['nullable', 'date'],
            'next_service_mileage' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string'],
        ]));

        return redirect()->route('automotive.vehicles')->with('success', 'Vehicle '.$vehicle->registration_number.' registered.');
    }

    public function bookings(AutomotiveFeatureGate $gate)
    {
        $gate->authorize('bookings.manage');

        return $this->view('Bookings', 'Workshop booking calendar, daily reception queue, service advisor workload, and capacity planning.', [
            'section' => 'bookings',
            'bookings' => ServiceBooking::with('client', 'vehicle', 'serviceAdvisor')->latest()->paginate(20),
            'checkIns' => CheckIn::with('vehicle', 'client')->latest()->limit(20)->get(),
        ]);
    }

    public function storeBooking(Request $request, BookingService $service, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('bookings.manage');
        $booking = $service->create($request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'vehicle_id' => ['nullable', 'exists:automotive_vehicles,id'],
            'service_advisor_id' => ['nullable', 'exists:users,id'],
            'requested_service' => ['nullable', 'string', 'max:255'],
            'preferred_date' => ['nullable', 'date'],
            'preferred_time' => ['nullable', 'date_format:H:i'],
            'customer_complaint' => ['nullable', 'string'],
            'pickup_required' => ['nullable', 'boolean'],
            'dropoff_required' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string'],
        ]));

        return redirect()->route('automotive.bookings')->with('success', 'Booking '.$booking->booking_number.' created.');
    }

    public function checkIn(Request $request, VehicleCheckInService $service, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('checkin.manage');
        $checkIn = $service->create($request->validate([
            'booking_id' => ['nullable', 'exists:automotive_service_bookings,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'vehicle_id' => ['required', 'exists:automotive_vehicles,id'],
            'service_advisor_id' => ['nullable', 'exists:users,id'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'fuel_level' => ['nullable', 'string', 'max:80'],
            'customer_complaint' => ['nullable', 'string'],
            'accessories' => ['nullable', 'array'],
            'keys_received' => ['nullable', 'boolean'],
            'expected_completion' => ['nullable', 'date'],
            'customer_authorization' => ['nullable', 'string', 'max:120'],
        ]));

        return redirect()->route('automotive.bookings')->with('success', 'Vehicle checked in as '.$checkIn->check_in_number.'.');
    }

    public function jobCards(AutomotiveFeatureGate $gate)
    {
        $gate->authorize('job_cards.view');

        return $this->view('Job Cards', 'Electronic job cards connect the customer, vehicle, inspection, estimate, labour, parts, QC, invoice, and release.', [
            'section' => 'job-cards',
            'jobs' => JobCard::with('client', 'vehicle', 'technician', 'serviceAdvisor', 'invoice')->latest()->paginate(20),
            'labourTasks' => LabourTask::with('jobCard', 'technician')->latest()->limit(30)->get(),
        ]);
    }

    public function storeJobCard(Request $request, JobCardService $service, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('job_cards.create');
        $job = $service->create($request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'vehicle_id' => ['required', 'exists:automotive_vehicles,id'],
            'booking_id' => ['nullable', 'exists:automotive_service_bookings,id'],
            'check_in_id' => ['nullable', 'exists:automotive_check_ins,id'],
            'service_advisor_id' => ['nullable', 'exists:users,id'],
            'technician_id' => ['nullable', 'exists:users,id'],
            'workshop_bay_id' => ['nullable', 'exists:automotive_workshop_bays,id'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'customer_complaint' => ['nullable', 'string'],
            'diagnosis' => ['nullable', 'string'],
            'work_requested' => ['nullable', 'string'],
            'estimated_completion' => ['nullable', 'date'],
            'priority' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', 'string', 'max:80'],
        ]));

        return redirect()->route('automotive.job-cards')->with('success', 'Job card '.$job->job_number.' opened.');
    }

    public function updateJobStatus(Request $request, JobCard $job, JobCardService $service, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('job_cards.update');
        $service->updateStatus($job, $request->validate(['status' => ['required', 'string', 'max:80']])['status']);

        return redirect()->route('automotive.job-cards')->with('success', 'Job status updated.');
    }

    public function storeLabourTask(Request $request, JobCard $job, JobCardService $service, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('job_cards.assign');
        $service->addLabourTask($job, $request->validate([
            'labour_operation_id' => ['nullable', 'exists:automotive_labour_operations,id'],
            'technician_id' => ['nullable', 'exists:users,id'],
            'description' => ['required', 'string', 'max:255'],
            'standard_hours' => ['nullable', 'numeric', 'min:0'],
            'billable_hours' => ['nullable', 'numeric', 'min:0'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string'],
        ]));

        return redirect()->route('automotive.job-cards')->with('success', 'Labour task added.');
    }

    public function invoiceJob(JobCard $job, AutomotiveFinanceService $finance, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('automotive.finance');
        $invoice = $finance->invoiceJob($job);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Automotive job invoice created.');
    }

    public function estimates(AutomotiveFeatureGate $gate)
    {
        $gate->authorize('estimates.manage');

        return $this->view('Estimates', 'Automotive estimates connect labour, parts, consumables, outside services, quotation conversion, and approval control.', [
            'section' => 'estimates',
            'estimates' => Estimate::with('client', 'vehicle', 'jobCard', 'items')->latest()->paginate(20),
        ]);
    }

    public function storeEstimate(Request $request, AutomotiveEstimateService $service, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('estimates.manage');
        $data = $request->validate([
            'job_card_id' => ['nullable', 'exists:automotive_job_cards,id'],
            'vehicle_id' => ['nullable', 'exists:automotive_vehicles,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'discount_total' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:80'],
            'items' => ['nullable', 'array'],
            'items.*.type' => ['nullable', 'string', 'max:80'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0'],
        ]);

        $items = $data['items'] ?? [];
        unset($data['items']);
        $estimate = $service->create($data, array_values(array_filter($items, fn ($item) => ! empty($item['description']))));

        return redirect()->route('automotive.estimates')->with('success', 'Estimate '.$estimate->estimate_number.' created.');
    }

    public function approveEstimate(Estimate $estimate, AutomotiveEstimateService $service, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('estimates.approve');
        $service->approve($estimate);

        return redirect()->route('automotive.estimates')->with('success', 'Estimate approved.');
    }

    public function estimateToQuotation(Estimate $estimate, AutomotiveEstimateService $service, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('estimates.approve');
        $quotation = $service->toQuotation($estimate);

        return redirect()->route('quotations.show', $quotation)->with('success', 'Quotation generated from automotive estimate.');
    }

    public function labourOperations(AutomotiveFeatureGate $gate)
    {
        $gate->authorize('technicians.manage');

        return $this->view('Labour Operations', 'Standard labour codes, hours, rates, skills, and workshop costing templates.', [
            'section' => 'labour-operations',
            'operations' => LabourOperation::latest()->paginate(20),
        ]);
    }

    public function storeLabourOperation(Request $request, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('technicians.manage');
        $operation = LabourOperation::create($request->validate([
            'labour_code' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'standard_hours' => ['nullable', 'numeric', 'min:0'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'skill_required' => ['nullable', 'string', 'max:120'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]));

        return redirect()->route('automotive.labour-operations')->with('success', 'Labour operation '.$operation->labour_code.' saved.');
    }

    public function technicians(AutomotiveFeatureGate $gate)
    {
        $gate->authorize('technicians.manage');

        return $this->view('Technicians', 'Technician assignment, workload, specialist skills, active jobs, and productivity visibility.', [
            'section' => 'technicians',
            'technicians' => User::where('is_active', true)->orWhere('status', 'Active')->orderBy('name')->paginate(20),
        ]);
    }

    public function workshop(WorkshopService $workshop, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('workshop.manage');

        return $this->view('Workshop Board', 'Garage dashboard, job status columns, workshop bays, diagnostics, technician load, and repair flow.', [
            'section' => 'workshop',
            'board' => $workshop->board(),
            'bays' => WorkshopBay::with('assignedJobCard', 'assignedTechnician')->latest()->get(),
        ]);
    }

    public function storeBay(Request $request, WorkshopService $workshop, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('workshop.manage');
        $bay = $workshop->bay($request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['nullable', 'string', 'max:120'],
            'assigned_technician_id' => ['nullable', 'exists:users,id'],
            'status' => ['nullable', 'string', 'max:80'],
        ]));

        return redirect()->route('automotive.workshop')->with('success', 'Workshop bay '.$bay->name.' saved.');
    }

    public function storeDiagnostic(Request $request, JobCard $job, WorkshopService $workshop, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('workshop.manage');
        $diagnostic = $workshop->diagnostic($job, $request->validate([
            'technician_id' => ['nullable', 'exists:users,id'],
            'diagnostic_type' => ['nullable', 'string', 'max:120'],
            'fault_codes' => ['nullable', 'array'],
            'symptoms' => ['nullable', 'string'],
            'diagnosis' => ['nullable', 'string'],
            'recommended_repair' => ['nullable', 'string'],
        ]));

        return redirect()->route('automotive.workshop')->with('success', 'Diagnostic '.$diagnostic->diagnostic_number.' recorded.');
    }

    public function inspections(AutomotiveFeatureGate $gate)
    {
        $gate->authorize('inspections.create');

        return $this->view('Inspections', 'Digital inspection sections, visual status indicators, technician notes, photos-ready architecture, and estimate recommendations.', [
            'section' => 'inspections',
            'inspections' => Inspection::with('vehicle', 'jobCard', 'technician', 'items')->latest()->paginate(20),
        ]);
    }

    public function storeInspection(Request $request, InspectionService $service, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('inspections.create');
        $inspection = $service->create($request->validate([
            'vehicle_id' => ['required', 'exists:automotive_vehicles,id'],
            'check_in_id' => ['nullable', 'exists:automotive_check_ins,id'],
            'job_card_id' => ['nullable', 'exists:automotive_job_cards,id'],
            'technician_id' => ['nullable', 'exists:users,id'],
            'inspection_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:80'],
            'recommendations' => ['nullable', 'string'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
        ]));

        return redirect()->route('automotive.inspections')->with('success', 'Inspection '.$inspection->inspection_number.' created.');
    }

    public function inspectionToEstimate(Inspection $inspection, InspectionService $service, AutomotiveEstimateService $estimates, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('estimates.manage');
        $estimate = $service->recommendationsToEstimate($inspection, $estimates);

        return redirect()->route('automotive.job-cards')->with('success', 'Estimate '.$estimate->estimate_number.' created from inspection.');
    }

    public function parts(AutomotiveFeatureGate $gate)
    {
        $gate->authorize('parts.view');

        return $this->view('Parts', 'Automotive parts catalog, compatibility, reservations, requests, issuing, returns, and low-stock control.', [
            'section' => 'parts',
            'parts' => Part::with('product', 'supplier')->latest()->paginate(20),
            'partRequests' => PartRequest::with('jobCard.vehicle', 'items.part')->latest()->paginate(10, ['*'], 'request_page'),
        ]);
    }

    public function storePart(Request $request, AutomotiveInventoryService $inventory, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('automotive.inventory');
        $part = $inventory->part($request->validate([
            'product_id' => ['nullable', 'exists:products,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'part_number' => ['required', 'string', 'max:120'],
            'oem_number' => ['nullable', 'string', 'max:120'],
            'barcode' => ['nullable', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:120'],
            'brand' => ['nullable', 'string', 'max:120'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'bin_location' => ['nullable', 'string', 'max:120'],
            'warranty_period_days' => ['nullable', 'integer', 'min:0'],
        ]));

        return redirect()->route('automotive.parts')->with('success', 'Part '.$part->part_number.' saved.');
    }

    public function storePartRequest(Request $request, JobCard $job, AutomotiveInventoryService $inventory, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('parts.issue');
        $data = $request->validate([
            'part_id' => ['nullable', 'exists:automotive_parts,id'],
            'part_name' => ['nullable', 'string', 'max:255'],
            'requested_qty' => ['required', 'numeric', 'min:0.001'],
        ]);

        $part = ! empty($data['part_id']) ? Part::find($data['part_id']) : null;
        $requestRecord = $inventory->request($job, ['requested_by' => auth()->id()], [[
            'part_id' => $part?->id,
            'part_name' => $data['part_name'] ?? $part?->name,
            'requested_qty' => $data['requested_qty'],
        ]]);

        return redirect()->route('automotive.parts')->with('success', 'Parts request '.$requestRecord->request_number.' created.');
    }

    public function issuePart(Request $request, PartRequestItem $item, AutomotiveInventoryService $inventory, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('parts.issue');
        $inventory->issue($item, (float) $request->validate(['quantity' => ['required', 'numeric', 'min:0.001']])['quantity']);

        return redirect()->route('automotive.parts')->with('success', 'Part issued to job.');
    }

    public function returnPart(Request $request, PartRequestItem $item, AutomotiveInventoryService $inventory, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('parts.return');
        $inventory->return($item, (float) $request->validate(['quantity' => ['required', 'numeric', 'min:0.001']])['quantity']);

        return redirect()->route('automotive.parts')->with('success', 'Part returned to stock.');
    }

    public function quality(AutomotiveFeatureGate $gate)
    {
        $gate->authorize('quality_control.manage');

        return $this->view('Quality', 'QC checks, road tests, comeback tracking, and vehicle release controls.', [
            'section' => 'quality',
            'qualityChecks' => QualityCheck::with('jobCard.vehicle', 'inspector')->latest()->paginate(12),
            'roadTests' => RoadTest::with('jobCard.vehicle', 'tester')->latest()->limit(20)->get(),
            'releases' => VehicleRelease::with('jobCard.vehicle')->latest()->limit(20)->get(),
            'comebacks' => Comeback::with('vehicle', 'originalJobCard')->latest()->limit(20)->get(),
        ]);
    }

    public function storeQuality(Request $request, JobCard $job, QualityControlService $quality, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('quality_control.manage');
        $qc = $quality->quality($job, $request->validate([
            'inspector_id' => ['nullable', 'exists:users,id'],
            'checklist' => ['nullable', 'array'],
            'result' => ['required', Rule::in(['Pass', 'Conditional Pass', 'Fail'])],
            'failure_reason' => ['nullable', 'string'],
            'corrective_action' => ['nullable', 'string'],
        ]));

        return redirect()->route('automotive.quality')->with('success', 'QC '.$qc->qc_number.' recorded.');
    }

    public function storeRoadTest(Request $request, JobCard $job, QualityControlService $quality, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('quality_control.manage');
        $test = $quality->roadTest($job, $request->validate([
            'tester_id' => ['nullable', 'exists:users,id'],
            'start_mileage' => ['nullable', 'integer', 'min:0'],
            'end_mileage' => ['nullable', 'integer', 'min:0'],
            'test_result' => ['required', Rule::in(['Passed', 'Failed', 'Not Required'])],
            'observations' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ]));

        return redirect()->route('automotive.quality')->with('success', 'Road test '.$test->road_test_number.' recorded.');
    }

    public function roadTests(AutomotiveFeatureGate $gate)
    {
        $gate->authorize('quality_control.manage');

        return $this->view('Road Tests', 'Road test planning, mileage, observations, tester accountability, and release readiness.', [
            'section' => 'road-tests',
            'roadTests' => RoadTest::with('jobCard.vehicle', 'tester')->latest()->paginate(20),
        ]);
    }

    public function releaseVehicle(Request $request, JobCard $job, QualityControlService $quality, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('job_cards.complete');
        $release = $quality->release($job, $request->validate([
            'invoice_id' => ['nullable', 'exists:invoices,id'],
            'released_by' => ['nullable', 'exists:users,id'],
            'final_mileage' => ['nullable', 'integer', 'min:0'],
            'payment_status' => ['nullable', 'string', 'max:80'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_acknowledgement' => ['nullable', 'string', 'max:120'],
            'override_unpaid' => ['nullable', 'boolean'],
        ]));

        return redirect()->route('automotive.quality')->with('success', 'Vehicle released as '.$release->release_number.'.');
    }

    public function vehicleRelease(AutomotiveFeatureGate $gate)
    {
        $gate->authorize('job_cards.complete');

        return $this->view('Vehicle Release', 'Final invoice/payment check, handover acknowledgement, release notes, and collection audit trail.', [
            'section' => 'vehicle-release',
            'releases' => VehicleRelease::with('jobCard.vehicle')->latest()->paginate(20),
        ]);
    }

    public function warranty(AutomotiveFeatureGate $gate)
    {
        $gate->authorize('warranty.manage');

        return $this->view('Warranty', 'Parts, labour, vehicle warranty records, warranty expiries, claims, and comeback analytics.', [
            'section' => 'warranty',
            'warranties' => Warranty::with('vehicle', 'jobCard', 'part')->latest()->paginate(20),
            'comebacks' => Comeback::with('vehicle', 'originalJobCard')->latest()->limit(30)->get(),
        ]);
    }

    public function storeWarranty(Request $request, WarrantyService $service, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('warranty.manage');
        $warranty = $service->warranty($request->validate([
            'job_card_id' => ['nullable', 'exists:automotive_job_cards,id'],
            'vehicle_id' => ['nullable', 'exists:automotive_vehicles,id'],
            'part_id' => ['nullable', 'exists:automotive_parts,id'],
            'type' => ['nullable', 'string', 'max:120'],
            'warranty_start' => ['nullable', 'date'],
            'warranty_end' => ['nullable', 'date'],
            'mileage_limit' => ['nullable', 'integer', 'min:0'],
            'terms' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:80'],
        ]));

        return redirect()->route('automotive.warranty')->with('success', 'Warranty '.$warranty->warranty_number.' created.');
    }

    public function storeComeback(Request $request, JobCard $job, WarrantyService $service, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('warranty.manage');
        $comeback = $service->comeback($job, $request->validate([
            'technician_id' => ['nullable', 'exists:users,id'],
            'complaint' => ['required', 'string'],
            'return_date' => ['nullable', 'date'],
            'cause' => ['nullable', 'string'],
            'warranty' => ['nullable', 'boolean'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'resolution' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:80'],
        ]));

        return redirect()->route('automotive.warranty')->with('success', 'Comeback '.$comeback->comeback_number.' opened.');
    }

    public function fleet(AutomotiveFeatureGate $gate)
    {
        $gate->authorize('fleet.manage');

        return $this->view('Fleet', 'Fleet companies, vehicles, service rules, maintenance spend, downtime, and cost-per-vehicle visibility.', [
            'section' => 'fleet',
            'fleets' => Fleet::with('client', 'vehicles')->latest()->paginate(20),
            'reminders' => ServiceReminder::with('vehicle')->latest()->limit(30)->get(),
        ]);
    }

    public function storeFleet(Request $request, FleetService $service, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('fleet.manage');
        $fleet = $service->create($request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'fleet_manager_id' => ['nullable', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'service_rules' => ['nullable', 'array'],
            'credit_terms' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'max:80'],
        ]));

        return redirect()->route('automotive.fleet')->with('success', 'Fleet '.$fleet->fleet_number.' created.');
    }

    public function jobCosting(AutomotiveFeatureGate $gate)
    {
        $gate->authorize('automotive.finance');

        return $this->view('Job Costing', 'Estimated versus actual labour, parts, consumables, outsourced costs, revenue, profit, and margin.', [
            'section' => 'job-costing',
            'jobCosts' => \Modules\Automotive\Models\JobCost::with('jobCard.vehicle')->latest()->paginate(20),
        ]);
    }

    public function costJob(Request $request, JobCard $job, AutomotiveFinanceService $finance, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('automotive.finance');
        $cost = $finance->costing($job, $request->validate([
            'parts_cost' => ['nullable', 'numeric', 'min:0'],
            'labour_cost' => ['nullable', 'numeric', 'min:0'],
            'consumables_cost' => ['nullable', 'numeric', 'min:0'],
            'outsourced_cost' => ['nullable', 'numeric', 'min:0'],
            'transport_cost' => ['nullable', 'numeric', 'min:0'],
            'other_cost' => ['nullable', 'numeric', 'min:0'],
            'revenue' => ['nullable', 'numeric', 'min:0'],
        ]));

        return redirect()->route('automotive.job-costing')->with('success', 'Costing updated for '.$cost->jobCard?->job_number.'.');
    }

    public function serviceReminders(AutomotiveFeatureGate $gate)
    {
        $gate->authorize('vehicles.update');

        return $this->view('Service Reminders', 'Service due dates, mileage triggers, open reminder queue, and customer follow-up.', [
            'section' => 'service-reminders',
            'reminders' => ServiceReminder::with('vehicle')->latest()->paginate(20),
        ]);
    }

    public function storeServiceReminder(Request $request, VehicleService $service, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('vehicles.update');
        $data = $request->validate([
            'vehicle_id' => ['required', 'exists:automotive_vehicles,id'],
            'type' => ['nullable', 'string', 'max:120'],
            'due_date' => ['nullable', 'date'],
            'due_mileage' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string', 'max:80'],
        ]);
        $vehicle = Vehicle::findOrFail($data['vehicle_id']);
        unset($data['vehicle_id']);
        $reminder = $service->reminder($vehicle, $data);

        return redirect()->route('automotive.service-reminders')->with('success', 'Reminder '.$reminder->reminder_number.' created.');
    }

    public function sales(AutomotiveFeatureGate $gate)
    {
        $gate->authorize('vehicle_sales.manage');

        return $this->view('Vehicle Sales', 'Dealership stock, test drives, trade-ins, sales pipeline, reconditioning costs, and delivery controls.', [
            'section' => 'sales',
            'vehicleSales' => VehicleSale::with('client', 'salesperson')->latest()->paginate(20),
            'testDrives' => TestDrive::with('client', 'vehicleSale')->latest()->limit(20)->get(),
            'tradeIns' => TradeIn::with('client', 'vehicleSale')->latest()->limit(20)->get(),
        ]);
    }

    public function storeVehicleSale(Request $request, AutomotiveNumberService $numbers, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('vehicle_sales.manage');
        $sale = VehicleSale::create($request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'salesperson_id' => ['nullable', 'exists:users,id'],
            'vin' => ['nullable', 'string', 'max:120'],
            'registration_number' => ['nullable', 'string', 'max:80'],
            'make' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'year' => ['nullable', 'integer', 'min:1900'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'reconditioning_cost' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:80'],
        ]) + ['stock_number' => $numbers->next('STK', VehicleSale::class, 'stock_number')]);

        return redirect()->route('automotive.sales')->with('success', 'Vehicle stock '.$sale->stock_number.' saved.');
    }

    public function specialty(string $type, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('automotive.view');
        abort_unless(in_array($type, ['tyres', 'body-paint', 'insurance-repairs'], true), 404);

        $titles = [
            'tyres' => ['Tyres', 'Tyre fitment, alignment, balancing, tyre condition, and tyre job notes.'],
            'body-paint' => ['Body & Paint', 'Damage assessment, panel work, paint stage, photos-ready notes, and repair status.'],
            'insurance-repairs' => ['Insurance Repairs', 'Insurance claim repairs, assessor notes, claim references, approvals, and repair status.'],
        ];

        return $this->view($titles[$type][0], $titles[$type][1], [
            'section' => 'specialty',
            'specialtyType' => $type,
            'specialtyTitle' => $titles[$type][0],
            'records' => SpecialtyRecord::with('vehicle', 'jobCard')->where('type', $type)->latest()->paginate(20),
        ]);
    }

    public function storeSpecialty(Request $request, string $type, AutomotiveNumberService $numbers, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('automotive.view');
        abort_unless(in_array($type, ['tyres', 'body-paint', 'insurance-repairs'], true), 404);

        $record = SpecialtyRecord::create([
            ...$request->validate([
                'vehicle_id' => ['nullable', 'exists:automotive_vehicles,id'],
                'job_card_id' => ['nullable', 'exists:automotive_job_cards,id'],
                'status' => ['nullable', 'string', 'max:80'],
                'payload' => ['nullable', 'array'],
            ]),
            'type' => $type,
            'record_number' => $numbers->next(strtoupper(str_replace('-', '', substr($type, 0, 3))), SpecialtyRecord::class, 'record_number'),
        ]);

        return redirect()->route('automotive.specialty', $type)->with('success', $record->record_number.' saved.');
    }

    public function customerService(AutomotiveFeatureGate $gate)
    {
        $gate->authorize('automotive.view');

        return $this->view('Customer Service', 'Customer feedback, satisfaction scores, complaint management, and service quality follow-up.', [
            'section' => 'customer-service',
            'feedback' => CustomerFeedback::with('client', 'vehicle', 'jobCard')->latest()->paginate(20),
            'complaints' => Complaint::with('client', 'vehicle', 'jobCard', 'assignedEmployee')->latest()->limit(30)->get(),
        ]);
    }

    public function storeFeedback(Request $request, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('automotive.view');
        CustomerFeedback::create($request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'vehicle_id' => ['nullable', 'exists:automotive_vehicles,id'],
            'job_card_id' => ['nullable', 'exists:automotive_job_cards,id'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'scores' => ['nullable', 'array'],
            'comments' => ['nullable', 'string'],
        ]));

        return redirect()->route('automotive.customer-service')->with('success', 'Customer feedback recorded.');
    }

    public function storeComplaint(Request $request, AutomotiveNumberService $numbers, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('automotive.view');
        $complaint = Complaint::create($request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'vehicle_id' => ['nullable', 'exists:automotive_vehicles,id'],
            'job_card_id' => ['nullable', 'exists:automotive_job_cards,id'],
            'assigned_employee_id' => ['nullable', 'exists:users,id'],
            'category' => ['nullable', 'string', 'max:120'],
            'description' => ['required', 'string'],
            'priority' => ['nullable', 'string', 'max:80'],
            'resolution' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:80'],
        ]) + ['complaint_number' => $numbers->next('CMP', Complaint::class, 'complaint_number')]);

        return redirect()->route('automotive.customer-service')->with('success', 'Complaint '.$complaint->complaint_number.' opened.');
    }

    public function reports(AutomotiveReportingService $reports, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('automotive.reports');

        return $this->view('Reports', 'Workshop, revenue, profitability, technician, parts, fleet, customer, and quality reporting.', [
            'section' => 'reports',
            'summary' => $reports->summary(),
        ]);
    }

    public function reportCsv(string $type, AutomotiveReportingService $reports, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('automotive.reports');

        return $reports->csv($type);
    }

    public function mobile(AutomotiveDashboardService $dashboard, AutomotiveFeatureGate $gate)
    {
        $gate->authorize('job_cards.update');

        return $this->view('Mobile Technician', 'Technician-first job controls for phones and tablets.', [
            'section' => 'mobile',
            'mobileActions' => $dashboard->mobileActions(),
            'myJobs' => JobCard::with('vehicle', 'client')->where('technician_id', auth()->id())->latest()->get(),
        ]);
    }

    private function view(string $title, string $description, array $data = [])
    {
        return view('automotive.operations', $data + [
            'title' => $title,
            'description' => $description,
            'clients' => Client::latest()->limit(200)->get(),
            'vehiclesList' => Vehicle::with('client')->latest()->limit(200)->get(),
            'bookingsList' => ServiceBooking::latest()->limit(100)->get(),
            'checkInsList' => CheckIn::latest()->limit(100)->get(),
            'jobsList' => JobCard::with('vehicle')->latest()->limit(120)->get(),
            'users' => User::where('is_active', true)->orWhere('status', 'Active')->orderBy('name')->limit(200)->get(),
            'partsList' => Part::latest()->limit(200)->get(),
            'baysList' => WorkshopBay::latest()->limit(100)->get(),
            'labourOperations' => LabourOperation::latest()->limit(100)->get(),
            'labourTasks' => LabourTask::latest()->limit(200)->get(),
            'packages' => ServicePackage::latest()->limit(100)->get(),
            'specialtyRecords' => SpecialtyRecord::latest()->limit(30)->get(),
        ]);
    }
}
