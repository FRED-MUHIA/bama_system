<?php

namespace Modules\Chama\Services;

use Illuminate\Support\Facades\DB;
use Modules\Chama\Models\DividendAllocation;
use Modules\Chama\Models\DividendRun;
use Modules\Chama\Models\Member;

class DividendService
{
    public function __construct(private ChamaNumberService $numbers, private ChamaAuditService $audit) {}

    public function createRun(array $data): DividendRun
    {
        return DB::transaction(function () use ($data) {
            $run = DividendRun::create(array_merge($data, [
                'dividend_number' => $data['dividend_number'] ?? $this->numbers->dividendNumber(),
                'amount_distributed' => $data['amount_distributed'] ?? max((float) ($data['profit_available'] ?? 0) - (float) ($data['reserve_allocation'] ?? 0), 0),
                'status' => $data['status'] ?? 'Draft',
            ]));

            $this->allocate($run);
            $this->audit->record('dividend.run.created', $run);

            return $run->refresh();
        });
    }

    public function allocate(DividendRun $run): int
    {
        if ($run->allocations()->exists()) {
            return 0;
        }

        $members = Member::where('status', 'Active')->get();
        $basis = $members->mapWithKeys(fn ($member) => [$member->id => $this->basisFor($member, $run->distribution_basis)]);
        $basisTotal = max((float) $basis->sum(), 0);
        $created = 0;

        foreach ($members as $member) {
            $memberBasis = (float) ($basis[$member->id] ?? 0);
            $percentage = $basisTotal > 0 ? $memberBasis / $basisTotal : ($members->count() ? 1 / $members->count() : 0);
            $gross = round((float) $run->amount_distributed * $percentage, 2);

            DividendAllocation::create([
                'dividend_run_id' => $run->id,
                'member_id' => $member->id,
                'member_basis' => $memberBasis,
                'percentage' => round($percentage * 100, 4),
                'gross_dividend' => $gross,
                'deductions' => 0,
                'net_dividend' => $gross,
                'payment_status' => 'Pending',
            ]);
            $created++;
        }

        return $created;
    }

    private function basisFor(Member $member, string $basis): float
    {
        return match ($basis) {
            'Savings' => (float) $member->savingsAccounts()->sum('current_balance'),
            'Contributions' => (float) $member->contributions()->sum('amount_paid'),
            default => (float) $member->shareTransactions()->sum('share_value'),
        };
    }
}
