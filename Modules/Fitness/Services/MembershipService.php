<?php

namespace Modules\Fitness\Services;

use App\Models\Client;
use App\Support\ActiveBusiness;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Modules\Fitness\Models\Member;
use Modules\Fitness\Models\MemberMembership;
use Modules\Fitness\Models\MembershipPlan;

class MembershipService
{
    public function __construct(private FitnessNumberService $numbers)
    {
    }

    public function createMember(array $clientData, array $memberData): Member
    {
        return DB::transaction(function () use ($clientData, $memberData) {
            $client = ! empty($memberData['client_id'])
                ? Client::whereKey($memberData['client_id'])->firstOrFail()
                : Client::create($clientData);

            return Member::create($memberData + [
                'client_id' => $client->id,
                'member_number' => $this->numbers->memberNumber(),
                'qr_code' => $this->numbers->qrCode(),
                'join_date' => $memberData['join_date'] ?? now()->toDateString(),
                'status' => $memberData['status'] ?? 'Pending',
            ])->load('client', 'assignedTrainer');
        });
    }

    public function enroll(Member $member, MembershipPlan $plan, array $data): MemberMembership
    {
        return DB::transaction(function () use ($member, $plan, $data) {
            $this->cancelActiveMemberships($member);

            $startsAt = \Illuminate\Support\Carbon::parse($data['starts_at'] ?? now()->toDateString())->startOfDay();
            $endsAt = $startsAt->copy()->addDays(max((int) $plan->duration_days, 1) - 1);
            $price = round((float) ($data['price_charged'] ?? $plan->price), 2);
            $joiningFee = round((float) ($data['joining_fee_charged'] ?? $plan->joining_fee), 2);
            $balance = round((float) ($data['balance'] ?? ($price + $joiningFee)), 2);

            $membership = MemberMembership::create([
                'fitness_member_id' => $member->id,
                'fitness_membership_plan_id' => $plan->id,
                'membership_number' => $this->numbers->membershipNumber(),
                'starts_at' => $startsAt->toDateString(),
                'ends_at' => $endsAt->toDateString(),
                'renewal_date' => $endsAt->copy()->addDay()->toDateString(),
                'auto_renew' => (bool) ($data['auto_renew'] ?? false),
                'status' => $data['status'] ?? 'Active',
                'session_credits_remaining' => $plan->session_credits,
                'guest_passes_remaining' => (int) ($plan->guest_passes ?? 0),
                'price_charged' => $price,
                'joining_fee_charged' => $joiningFee,
                'balance' => $balance,
            ]);

            $member->update(['status' => $membership->status]);
            $this->event($membership, 'membership.enrolled', null, $membership->fresh()->toArray());

            return $membership->load('member.client', 'plan');
        });
    }

    public function renew(MemberMembership $membership, ?MembershipPlan $plan = null, array $data = []): MemberMembership
    {
        return DB::transaction(function () use ($membership, $plan, $data) {
            $membership = MemberMembership::lockForUpdate()->findOrFail($membership->id);
            $old = $membership->toArray();
            $plan ??= $membership->plan;

            $startsAt = $membership->ends_at && $membership->ends_at->isFuture()
                ? $membership->ends_at->copy()->addDay()
                : now()->startOfDay();
            $endsAt = $startsAt->copy()->addDays(max((int) $plan->duration_days, 1) - 1);
            $charge = round((float) ($data['price_charged'] ?? ($plan->renewal_fee > 0 ? $plan->renewal_fee : $plan->price)), 2);

            $membership->update([
                'fitness_membership_plan_id' => $plan->id,
                'starts_at' => $startsAt->toDateString(),
                'ends_at' => $endsAt->toDateString(),
                'renewal_date' => $endsAt->copy()->addDay()->toDateString(),
                'status' => 'Active',
                'session_credits_remaining' => $plan->session_credits,
                'guest_passes_remaining' => (int) ($plan->guest_passes ?? 0),
                'price_charged' => $charge,
                'balance' => round((float) $membership->balance + $charge, 2),
                'last_renewed_at' => now(),
            ]);

            $membership->member()->update(['status' => 'Active']);
            $this->event($membership, 'membership.renewed', $old, $membership->fresh()->toArray());

            return $membership->refresh()->load('member.client', 'plan');
        });
    }

    public function freeze(MemberMembership $membership, array $data): MemberMembership
    {
        return DB::transaction(function () use ($membership, $data) {
            $membership = MemberMembership::with('plan')->lockForUpdate()->findOrFail($membership->id);
            if (! $membership->plan->freeze_allowed) {
                throw ValidationException::withMessages(['membership' => 'This membership plan does not allow freezes.']);
            }

            $startsAt = \Illuminate\Support\Carbon::parse($data['starts_at'])->startOfDay();
            $endsAt = \Illuminate\Support\Carbon::parse($data['ends_at'])->startOfDay();
            $days = $startsAt->diffInDays($endsAt) + 1;
            $old = $membership->toArray();

            $membership->freezes()->create([
                'business_id' => ActiveBusiness::id(),
                'starts_at' => $startsAt->toDateString(),
                'ends_at' => $endsAt->toDateString(),
                'reason' => $data['reason'],
                'status' => 'Active',
                'created_by' => auth()->id(),
            ]);

            $membership->update([
                'status' => 'Frozen',
                'ends_at' => $membership->ends_at?->copy()->addDays($days)->toDateString(),
                'renewal_date' => $membership->renewal_date?->copy()->addDays($days)->toDateString(),
            ]);
            $membership->member()->update(['status' => 'Frozen']);
            $this->event($membership, 'membership.frozen', $old, $membership->fresh()->toArray());

            return $membership->refresh()->load('member.client', 'plan');
        });
    }

    public function expireDueMemberships(): int
    {
        $count = 0;

        MemberMembership::where('status', 'Active')
            ->whereNotNull('ends_at')
            ->whereDate('ends_at', '<', now()->toDateString())
            ->chunkById(100, function ($memberships) use (&$count) {
                foreach ($memberships as $membership) {
                    $old = $membership->toArray();
                    $membership->update(['status' => 'Expired']);
                    $membership->member()->update(['status' => 'Expired']);
                    $this->event($membership, 'membership.expired', $old, $membership->fresh()->toArray());
                    $count++;
                }
            });

        return $count;
    }

    public function sendExpiryReminders(): int
    {
        $count = 0;
        $dates = collect([7, 3, 1, 0])->mapWithKeys(fn (int $days) => [
            now()->addDays($days)->toDateString() => $days,
        ]);

        MemberMembership::with('member.client', 'plan')
            ->where('status', 'Active')
            ->whereIn('ends_at', $dates->keys())
            ->chunkById(100, function ($memberships) use ($dates, &$count) {
                foreach ($memberships as $membership) {
                    $days = $dates[$membership->ends_at->toDateString()] ?? null;
                    $event = 'membership.expiry.reminder.t-'.$days;

                    if ($membership->events()->where('event', $event)->whereDate('created_at', today())->exists()) {
                        continue;
                    }

                    $membership->events()->create([
                        'business_id' => $membership->business_id,
                        'user_id' => auth()->id(),
                        'event' => $event,
                        'notes' => 'Expiry reminder for '.$membership->ends_at->toDateString(),
                    ]);

                    if ($membership->member?->client?->email) {
                        try {
                            Mail::raw(
                                'Your '.$membership->plan?->name.' membership expires '.$membership->ends_at->format('d M Y').'. Please renew to keep access active.',
                                fn ($mail) => $mail->to($membership->member->client->email)->subject('Membership expiry reminder')
                            );
                        } catch (\Throwable $e) {
                            report($e);
                        }
                    }

                    $count++;
                }
            });

        return $count;
    }

    private function cancelActiveMemberships(Member $member): void
    {
        $member->memberships()->whereIn('status', ['Active', 'Frozen', 'Pending'])->update(['status' => 'Cancelled']);
    }

    private function event(MemberMembership $membership, string $event, ?array $old, ?array $new): void
    {
        $membership->events()->create([
            'business_id' => ActiveBusiness::id(),
            'user_id' => auth()->id(),
            'event' => $event,
            'old_values' => $old,
            'new_values' => $new,
        ]);

        app(\App\Services\IamService::class)->audit($event, $membership);
    }
}
