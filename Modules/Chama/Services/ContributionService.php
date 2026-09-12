<?php

namespace Modules\Chama\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Modules\Chama\Models\Contribution;
use Modules\Chama\Models\ContributionSchedule;
use Modules\Chama\Models\ContributionType;
use Modules\Chama\Models\Member;

class ContributionService
{
    public function __construct(
        private ChamaNumberService $numbers,
        private ChamaAuditService $audit,
    ) {}

    public function createType(array $data): ContributionType
    {
        return DB::transaction(function () use ($data) {
            $type = ContributionType::create(array_merge($data, [
                'type_number' => $data['type_number'] ?? $this->numbers->contributionTypeNumber(),
            ]));

            $this->audit->record('contribution_type.created', $type);

            return $type;
        });
    }

    public function generateSchedule(ContributionType $type, ?CarbonInterface $startsAt = null, int $periods = 1): int
    {
        return DB::transaction(function () use ($type, $startsAt, $periods) {
            $startsAt ??= now();
            $members = $this->applicableMembers($type);
            $created = 0;

            foreach ($members as $member) {
                for ($offset = 0; $offset < max(1, $periods); $offset++) {
                    $dueDate = $this->dueDateFor($type->frequency, $startsAt, $offset);
                    $period = $this->periodLabel($type->frequency, $dueDate);

                    $schedule = ContributionSchedule::firstOrCreate(
                        [
                            'contribution_type_id' => $type->id,
                            'member_id' => $member->id,
                            'period' => $period,
                        ],
                        [
                            'schedule_number' => $this->numbers->scheduleNumber(),
                            'due_date' => $dueDate->toDateString(),
                            'amount_due' => $type->amount,
                            'amount_paid' => 0,
                            'penalty' => 0,
                            'balance' => $type->amount,
                            'status' => 'Pending',
                        ]
                    );

                    $created += $schedule->wasRecentlyCreated ? 1 : 0;
                }
            }

            $this->audit->record('contribution_schedule.generated', $type, [], ['periods' => $periods, 'created' => $created]);

            return $created;
        });
    }

    public function recordPayment(array $data): Contribution
    {
        return DB::transaction(function () use ($data) {
            $schedule = ! empty($data['contribution_schedule_id'])
                ? ContributionSchedule::whereKey($data['contribution_schedule_id'])->lockForUpdate()->firstOrFail()
                : null;

            $amount = (float) ($data['amount_paid'] ?? $data['amount'] ?? 0);
            $memberId = $data['member_id'] ?? $schedule?->member_id;
            $typeId = $data['contribution_type_id'] ?? $schedule?->contribution_type_id;
            $amountDue = (float) ($data['amount_due'] ?? $schedule?->amount_due ?? $amount);
            $paidAfter = $schedule ? (float) $schedule->amount_paid + $amount : $amount;
            $balance = max($amountDue - $paidAfter, 0);
            $status = $balance <= 0 ? 'Paid' : ($paidAfter > 0 ? 'Partial' : 'Pending');

            $contribution = Contribution::create([
                'member_id' => $memberId,
                'contribution_type_id' => $typeId,
                'contribution_schedule_id' => $schedule?->id,
                'contribution_number' => $data['contribution_number'] ?? $this->numbers->contributionNumber(),
                'period' => $data['period'] ?? $schedule?->period,
                'amount_due' => $amountDue,
                'amount_paid' => $amount,
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'payment_method' => $data['payment_method'] ?? 'Cash',
                'payment_reference' => $data['payment_reference'] ?? null,
                'balance' => $balance,
                'status' => $status,
                'recorded_by' => auth()->id(),
                'posted_at' => now(),
            ]);

            if ($schedule) {
                $schedule->update([
                    'amount_paid' => $paidAfter,
                    'balance' => $balance,
                    'status' => $status,
                ]);
            }

            $this->audit->record('contribution.payment.recorded', $contribution);

            return $contribution;
        });
    }

    public function memberStatement(Member $member): array
    {
        return app(MemberService::class)->statement($member);
    }

    private function applicableMembers(ContributionType $type)
    {
        $ids = collect($type->applicable_member_ids ?? [])->filter()->values();

        return Member::query()
            ->where('status', 'Active')
            ->when($ids->isNotEmpty(), fn ($query) => $query->whereIn('id', $ids))
            ->get();
    }

    private function dueDateFor(string $frequency, CarbonInterface $start, int $offset)
    {
        $date = $start->copy();

        return match ($frequency) {
            'Daily' => $date->addDays($offset),
            'Weekly' => $date->addWeeks($offset),
            'Quarterly' => $date->addMonthsNoOverflow($offset * 3),
            'Annual' => $date->addYears($offset),
            'One-Time' => $date,
            default => $date->addMonthsNoOverflow($offset),
        };
    }

    private function periodLabel(string $frequency, CarbonInterface $date): string
    {
        return match ($frequency) {
            'Daily' => $date->format('Y-m-d'),
            'Weekly' => $date->format('o-\WW'),
            'Quarterly' => $date->format('Y').'-Q'.$date->quarter,
            'Annual' => $date->format('Y'),
            'One-Time' => 'One-Time-'.$date->format('Ymd'),
            default => $date->format('Y-m'),
        };
    }
}
