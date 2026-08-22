<?php

namespace Modules\RealEstate\Services;

use App\Models\Payment;
use App\Services\IamService;
use Illuminate\Support\Facades\DB;
use Modules\RealEstate\Models\Tenant;

class TenantOffboardingService
{
    public const STEPS = [
        'Notice Received',
        'Lease Termination',
        'Final Inspection',
        'Utility Reconciliation',
        'Final Billing',
        'Deposit Settlement',
        'Move Out',
        'Archive Tenant',
    ];

    public function __construct(private IamService $iam) {}

    public function startNotice(Tenant $tenant, array $data = []): Tenant
    {
        return $this->transition($tenant, 'Notice Received', [
            'status' => 'Notice Given',
            'notice_date' => $data['notice_date'] ?? now()->toDateString(),
            'move_out_date' => $data['move_out_date'] ?? $tenant->move_out_date,
            'offboarding_notes' => $data['offboarding_notes'] ?? $tenant->offboarding_notes,
        ], 'real-estate.tenant.notice-received');
    }

    public function progress(Tenant $tenant, string $step, array $data = []): Tenant
    {
        abort_unless(in_array($step, self::STEPS, true), 422, 'Invalid tenant exit workflow step.');

        $updates = ['offboarding_step' => $step];
        $event = 'real-estate.tenant.offboarding-step';

        if ($step === 'Lease Termination') {
            $updates['status'] = 'Moving Out';
            $updates['termination_date'] = $data['termination_date'] ?? now()->toDateString();
            $event = 'real-estate.tenant.lease-termination';
        }

        if ($step === 'Final Inspection') {
            $updates['final_inspection_date'] = $data['final_inspection_date'] ?? now()->toDateString();
        }

        if ($step === 'Utility Reconciliation') {
            $updates['utility_reconciled_at'] = now();
        }

        if ($step === 'Final Billing') {
            $updates['final_billed_at'] = now();
        }

        if ($step === 'Deposit Settlement') {
            $updates['deposit_settled_at'] = now();
        }

        if ($step === 'Move Out') {
            return $this->moveOut($tenant, $data);
        }

        if ($step === 'Archive Tenant') {
            return $this->archive($tenant, $data);
        }

        if (array_key_exists('offboarding_notes', $data)) {
            $updates['offboarding_notes'] = $data['offboarding_notes'];
        }

        return $this->transition($tenant, $step, $updates, $event);
    }

    public function moveOut(Tenant $tenant, array $data = []): Tenant
    {
        return DB::transaction(function () use ($tenant, $data) {
            $old = $tenant->getOriginal();

            $tenant->update([
                'status' => 'Moved Out',
                'offboarding_step' => 'Move Out',
                'move_out_date' => $data['move_out_date'] ?? now()->toDateString(),
                'offboarding_notes' => $data['offboarding_notes'] ?? $tenant->offboarding_notes,
            ]);

            $this->releaseTenantAssignments($tenant);
            $this->iam->audit('real-estate.tenant.moved-out', $tenant, $old);

            return $tenant->refresh();
        });
    }

    public function archive(Tenant $tenant, array $data = []): Tenant
    {
        return DB::transaction(function () use ($tenant, $data) {
            $old = $tenant->getOriginal();

            $tenant->update([
                'status' => 'Archived',
                'offboarding_step' => 'Archive Tenant',
                'archived_at' => now(),
                'archived_by' => auth()->id(),
                'move_out_date' => $data['move_out_date'] ?? $tenant->move_out_date ?? now()->toDateString(),
                'offboarding_notes' => $data['offboarding_notes'] ?? $tenant->offboarding_notes,
            ]);

            $this->releaseTenantAssignments($tenant);
            $this->iam->audit('real-estate.tenant.archived', $tenant, $old);

            return $tenant->refresh();
        });
    }

    public function restore(Tenant $tenant): Tenant
    {
        $old = $tenant->getOriginal();

        $tenant->update([
            'status' => 'Prospect',
            'offboarding_step' => null,
            'archived_at' => null,
            'archived_by' => null,
            'restored_at' => now(),
        ]);

        $this->iam->audit('real-estate.tenant.restored', $tenant, $old);

        return $tenant->refresh();
    }

    public function deleteIfAllowed(Tenant $tenant): void
    {
        if ($reason = $this->deleteBlockReason($tenant)) {
            abort(422, $reason);
        }

        $old = $tenant->getOriginal();
        $this->iam->audit('real-estate.tenant.deleted', $tenant, $old);
        $tenant->delete();
    }

    public function deleteBlockReason(Tenant $tenant): ?string
    {
        if ($tenant->leases()->exists()
            || $tenant->maintenanceRequests()->exists()
            || $tenant->serviceRequests()->exists()
            || $tenant->utilityBills()->exists()
            || $tenant->utilityReadings()->exists()
            || $tenant->amenityBookings()->exists()
            || $tenant->ledgerEntries()->exists()
            || $tenant->statements()->exists()
            || $tenant->documents()->exists()
            || $this->hasPayments($tenant)
        ) {
            return 'Tenant cannot be deleted because historical records exist. Archive the tenant instead.';
        }

        return null;
    }

    private function transition(Tenant $tenant, string $step, array $updates, string $event): Tenant
    {
        $old = $tenant->getOriginal();
        $tenant->update($updates + ['offboarding_step' => $step]);
        $this->iam->audit($event, $tenant, $old);

        return $tenant->refresh();
    }

    private function releaseTenantAssignments(Tenant $tenant): void
    {
        $tenant->leases()->whereIn('status', ['Draft', 'Active', 'Renewed'])->get()->each(function ($lease) {
            $old = $lease->getOriginal();
            $lease->update([
                'status' => 'Terminated',
                'auto_billing' => false,
                'next_bill_date' => null,
                'end_date' => $lease->end_date ?? now()->toDateString(),
            ]);
            $lease->unit?->update(['occupancy_status' => 'Vacant']);
            $lease->property?->update(['status' => 'Available']);
            $this->iam->audit('real-estate.lease.closed', $lease, $old);
        });

        $tenant->utilityMeters()->update(['real_estate_tenant_id' => null, 'status' => 'Inactive']);
        $tenant->maintenanceRequests()->whereIn('status', ['Open', 'Assigned', 'In Progress'])->update(['status' => 'Closed', 'resolved_at' => now()]);
        $tenant->serviceRequests()->whereIn('status', ['Open', 'Assigned', 'In Progress', 'Resolved'])->update(['status' => 'Closed', 'resolved_at' => now()]);
        $this->iam->audit('real-estate.unit.released', $tenant);
    }

    private function hasPayments(Tenant $tenant): bool
    {
        $invoiceIds = $tenant->ledgerEntries()->whereNotNull('invoice_id')->pluck('invoice_id')
            ->merge($tenant->utilityBills()->whereNotNull('invoice_id')->pluck('invoice_id'))
            ->merge($tenant->amenityBookings()->whereNotNull('invoice_id')->pluck('invoice_id'))
            ->merge($tenant->leases()->with('charges')->get()->flatMap(fn ($lease) => $lease->charges->pluck('invoice_id')))
            ->filter()
            ->unique();

        return $invoiceIds->isNotEmpty() && Payment::whereIn('invoice_id', $invoiceIds)->exists();
    }
}
