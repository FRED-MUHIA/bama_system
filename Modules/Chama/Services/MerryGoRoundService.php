<?php

namespace Modules\Chama\Services;

use Illuminate\Support\Facades\DB;
use Modules\Chama\Models\MerryGoRoundCycle;
use Modules\Chama\Models\MerryGoRoundMember;
use Modules\Chama\Models\MerryGoRoundRound;

class MerryGoRoundService
{
    public function __construct(
        private ChamaNumberService $numbers,
        private ChamaAuditService $audit,
    ) {}

    public function createCycle(array $data): MerryGoRoundCycle
    {
        return DB::transaction(function () use ($data) {
            $cycle = MerryGoRoundCycle::create(array_merge(
                collect($data)->except('member_ids')->all(),
                [
                    'cycle_number' => $data['cycle_number'] ?? $this->numbers->cycleNumber(),
                    'payout_amount' => $data['payout_amount'] ?? 0,
                    'status' => $data['status'] ?? 'Planned',
                ]
            ));

            foreach (array_values($data['member_ids'] ?? []) as $index => $memberId) {
                MerryGoRoundMember::create([
                    'cycle_id' => $cycle->id,
                    'member_id' => $memberId,
                    'payout_order' => $index + 1,
                    'status' => 'Active',
                ]);
            }

            $this->audit->record('merry_go_round.cycle.created', $cycle);

            return $cycle->refresh();
        });
    }

    public function createRound(MerryGoRoundCycle $cycle, array $data): MerryGoRoundRound
    {
        return DB::transaction(function () use ($cycle, $data) {
            $memberCount = max(1, $cycle->members()->where('status', 'Active')->count());
            $expected = (float) ($data['expected_amount'] ?? ((float) $cycle->contribution_amount * $memberCount));
            $payout = (float) ($data['payout_amount'] ?? $expected);
            $round = MerryGoRoundRound::create([
                'cycle_id' => $cycle->id,
                'beneficiary_id' => $data['beneficiary_id'],
                'round_number' => $data['round_number'] ?? $this->numbers->roundNumber(),
                'round_index' => $data['round_index'] ?? ($cycle->rounds()->count() + 1),
                'due_date' => $data['due_date'] ?? $cycle->next_payout_date,
                'expected_amount' => $expected,
                'amount_collected' => $data['amount_collected'] ?? 0,
                'payout_amount' => $payout,
                'payout_method' => $data['payout_method'] ?? null,
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'payout_date' => $data['payout_date'] ?? null,
                'status' => $data['status'] ?? 'Pending',
            ]);

            $cycle->update([
                'current_beneficiary_id' => $data['beneficiary_id'],
                'status' => $cycle->status === 'Planned' ? 'Active' : $cycle->status,
            ]);

            $this->audit->record('merry_go_round.round.created', $round);

            return $round;
        });
    }
}
