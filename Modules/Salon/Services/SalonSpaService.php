<?php

namespace Modules\Salon\Services;

use App\Models\Client;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Modules\Salon\Contracts\SalonSpaServiceContract;
use Modules\Salon\Models\Appointment;
use Modules\Salon\Models\ClientProfile;
use Modules\Salon\Models\Commission;
use Modules\Salon\Models\Consultation;
use Modules\Salon\Models\GiftCard;
use Modules\Salon\Models\LoyaltyAccount;
use Modules\Salon\Models\Membership;
use Modules\Salon\Models\MembershipPlan;
use Modules\Salon\Models\ProductConsumption;
use Modules\Salon\Models\Service;
use Modules\Salon\Models\StaffProfile;
use Modules\Salon\Models\Treatment;
use Modules\Salon\Models\WellnessEnrollment;
use Modules\Salon\Models\WellnessProgram;

class SalonSpaService implements SalonSpaServiceContract
{
    public function __construct(
        private readonly SalonDashboardService $dashboard,
        private readonly SalonNumberService $numbers,
        private readonly StockService $stock,
    ) {}

    public function dashboard(): array
    {
        return [
            'metrics' => $this->dashboard->metrics(),
            'kpis' => $this->dashboard->kpis(),
            'reports' => $this->dashboard->reports(),
        ];
    }

    public function createService(array $data): Service
    {
        return Service::create($data);
    }

    public function createClientProfile(array $data): ClientProfile
    {
        return DB::transaction(function () use ($data) {
            $client = ! empty($data['client_id'])
                ? Client::findOrFail($data['client_id'])
                : Client::create([
                    'type' => 'Individual',
                    'name' => $data['name'],
                    'phone' => $data['phone'] ?? null,
                    'email' => $data['email'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ]);

            $profile = ClientProfile::firstOrCreate(
                ['client_id' => $client->id],
                [
                    'client_code' => $data['client_code'] ?? $this->numbers->client(),
                    'date_of_birth' => $data['date_of_birth'] ?? null,
                    'gender' => $data['gender'] ?? null,
                    'preferences' => $data['preferences'] ?? null,
                    'allergies' => $data['allergies'] ?? null,
                    'skin_hair_profile' => $data['skin_hair_profile'] ?? null,
                ]
            );

            LoyaltyAccount::firstOrCreate(['salon_client_profile_id' => $profile->id]);

            return $profile->load('client', 'loyaltyAccount');
        });
    }

    public function createStaffProfile(array $data): StaffProfile
    {
        return StaffProfile::create($data);
    }

    public function createMembershipPlan(array $data): MembershipPlan
    {
        return MembershipPlan::create([
            'name' => $data['name'],
            'billing_cycle' => $data['billing_cycle'] ?? 'Monthly',
            'price' => $data['price'] ?? 0,
            'visit_allowance' => $data['visit_allowance'] ?? null,
            'discount_rate' => $data['discount_rate'] ?? 0,
            'benefits' => $this->linesToArray($data['benefits'] ?? null),
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function enrollMembership(array $data): Membership
    {
        return DB::transaction(function () use ($data) {
            $plan = MembershipPlan::findOrFail($data['salon_membership_plan_id']);
            $startsOn = \Carbon\Carbon::parse($data['starts_on'] ?? today());
            $endsOn = ! empty($data['ends_on'])
                ? \Carbon\Carbon::parse($data['ends_on'])
                : match ($plan->billing_cycle) {
                    'Weekly' => $startsOn->copy()->addWeek(),
                    'Quarterly' => $startsOn->copy()->addMonths(3),
                    'Annual' => $startsOn->copy()->addYear(),
                    default => $startsOn->copy()->addMonth(),
                };

            $membership = Membership::create([
                'salon_client_profile_id' => $data['salon_client_profile_id'],
                'salon_membership_plan_id' => $plan->id,
                'invoice_id' => $data['invoice_id'] ?? null,
                'membership_number' => $data['membership_number'] ?? $this->numbers->membership(),
                'starts_on' => $startsOn,
                'ends_on' => $endsOn,
                'visits_remaining' => $data['visits_remaining'] ?? $plan->visit_allowance,
                'balance' => $data['balance'] ?? $plan->price,
                'status' => $data['status'] ?? 'Active',
            ]);

            if (! empty($data['bonus_points'])) {
                $this->awardLoyaltyPoints($membership->profile, (int) $data['bonus_points'], 'Membership bonus', $membership->membership_number);
            }

            return $membership->load('profile.client', 'profile.loyaltyAccount', 'plan');
        });
    }

    public function awardLoyaltyPoints(ClientProfile $profile, int $points, string $reason, ?string $reference = null): LoyaltyAccount
    {
        $points = max($points, 0);
        $account = LoyaltyAccount::firstOrCreate(['salon_client_profile_id' => $profile->id]);
        $ledger = $account->ledger ?? [];
        $ledger[] = ['type' => 'Bonus', 'points' => $points, 'reason' => $reason, 'reference' => $reference, 'at' => now()->toISOString()];

        $account->update([
            'points_balance' => $account->points_balance + $points,
            'lifetime_points' => $account->lifetime_points + $points,
            'last_activity_at' => now(),
            'ledger' => $ledger,
        ]);

        return $account->fresh();
    }

    public function createConsultation(array $data): Consultation
    {
        return Consultation::create([
            'salon_client_profile_id' => $data['salon_client_profile_id'],
            'salon_appointment_id' => $data['salon_appointment_id'] ?? null,
            'salon_staff_profile_id' => $data['salon_staff_profile_id'] ?? null,
            'consultation_type' => $data['consultation_type'] ?? 'Beauty',
            'observations' => $this->linesToArray($data['observations'] ?? null),
            'recommendations' => $this->linesToArray($data['recommendations'] ?? null),
            'contraindications' => $this->linesToArray($data['contraindications'] ?? null),
            'follow_up_date' => $data['follow_up_date'] ?? null,
        ])->load('profile.client', 'appointment', 'staff');
    }

    public function createTreatment(array $data): Treatment
    {
        return Treatment::create([
            'salon_client_profile_id' => $data['salon_client_profile_id'],
            'salon_appointment_id' => $data['salon_appointment_id'] ?? null,
            'salon_service_id' => $data['salon_service_id'] ?? null,
            'salon_staff_profile_id' => $data['salon_staff_profile_id'] ?? null,
            'name' => $data['name'],
            'performed_on' => $data['performed_on'] ?? today(),
            'notes' => $data['notes'] ?? null,
            'products_used' => $this->linesToArray($data['products_used'] ?? null),
            'aftercare' => $this->linesToArray($data['aftercare'] ?? null),
        ])->load('profile.client', 'appointment', 'service', 'staff');
    }

    public function createWellnessProgram(array $data): WellnessProgram
    {
        return WellnessProgram::create([
            'name' => $data['name'],
            'category' => $data['category'] ?? 'Wellness',
            'description' => $data['description'] ?? null,
            'duration_days' => $data['duration_days'] ?? 30,
            'price' => $data['price'] ?? 0,
            'milestones' => $this->linesToArray($data['milestones'] ?? null),
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function enrollWellnessProgram(array $data): WellnessEnrollment
    {
        $program = WellnessProgram::findOrFail($data['salon_wellness_program_id']);
        $startsOn = \Carbon\Carbon::parse($data['starts_on'] ?? today());
        $endsOn = ! empty($data['ends_on'])
            ? \Carbon\Carbon::parse($data['ends_on'])
            : $startsOn->copy()->addDays((int) $program->duration_days);

        return WellnessEnrollment::create([
            'salon_wellness_program_id' => $program->id,
            'salon_client_profile_id' => $data['salon_client_profile_id'],
            'starts_on' => $startsOn,
            'ends_on' => $endsOn,
            'status' => $data['status'] ?? 'Active',
            'progress' => $this->linesToArray($data['progress'] ?? null),
        ])->load('program', 'profile.client');
    }

    public function updateCommissionStatus(Commission $commission, string $status): Commission
    {
        $commission->update(['status' => $status]);

        return $commission->fresh(['staff', 'appointment']);
    }

    public function bookAppointment(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {
            $services = Service::whereIn('id', collect($data['services'] ?? [])->pluck('service_id')->filter())->get()->keyBy('id');
            $startsAt = \Carbon\Carbon::parse($data['starts_at']);
            $duration = 0;
            $subtotal = 0.0;
            $tax = 0.0;
            $discount = 0.0;

            $lines = collect($data['services'] ?? [])->map(function (array $line) use ($services, &$duration, &$subtotal, &$tax, &$discount) {
                $service = ! empty($line['service_id']) ? $services->get((int) $line['service_id']) : null;
                $minutes = (int) ($line['duration_minutes'] ?? $service?->duration_minutes ?? 30);
                $price = (float) ($line['unit_price'] ?? $service?->price ?? 0);
                $lineDiscount = (float) ($line['discount'] ?? 0);
                $lineTax = round(max($price - $lineDiscount, 0) * ((float) ($line['tax_rate'] ?? $service?->tax_rate ?? 0) / 100), 2);
                $lineTotal = max($price - $lineDiscount, 0) + $lineTax;

                $duration += $minutes;
                $subtotal += $price;
                $discount += $lineDiscount;
                $tax += $lineTax;

                return [
                    'salon_service_id' => $service?->id,
                    'salon_staff_profile_id' => $line['salon_staff_profile_id'] ?? $line['staff_profile_id'] ?? null,
                    'service_name' => $line['service_name'] ?? $service?->name ?? 'Salon service',
                    'duration_minutes' => $minutes,
                    'unit_price' => $price,
                    'discount' => $lineDiscount,
                    'tax' => $lineTax,
                    'line_total' => $lineTotal,
                    'status' => 'Pending',
                ];
            });

            $appointment = Appointment::create([
                'branch_id' => $data['branch_id'] ?? null,
                'client_id' => $data['client_id'] ?? null,
                'salon_client_profile_id' => $data['salon_client_profile_id'] ?? null,
                'salon_staff_profile_id' => $data['salon_staff_profile_id'] ?? null,
                'salon_resource_id' => $data['salon_resource_id'] ?? null,
                'appointment_number' => $data['appointment_number'] ?? $this->numbers->appointment(),
                'channel' => $data['channel'] ?? 'Walk-in',
                'starts_at' => $startsAt,
                'ends_at' => $data['ends_at'] ?? $startsAt->copy()->addMinutes(max($duration, 30)),
                'status' => $data['status'] ?? 'Booked',
                'payment_status' => $data['payment_status'] ?? 'Unpaid',
                'subtotal' => $subtotal,
                'discount_total' => $discount,
                'tax_total' => $tax,
                'total' => max($subtotal - $discount, 0) + $tax,
                'notes' => $data['notes'] ?? null,
            ]);

            $lines->each(fn (array $line) => $appointment->services()->create($line));

            return $appointment->load('client', 'profile.client', 'staff', 'resource', 'services.service');
        });
    }

    public function completeAppointment(Appointment $appointment, array $data = []): Appointment
    {
        return DB::transaction(function () use ($appointment, $data) {
            $appointment->loadMissing('profile.loyaltyAccount', 'services.service', 'staff');
            $appointment->update([
                'status' => $data['status'] ?? 'Completed',
                'payment_status' => $data['payment_status'] ?? $appointment->payment_status,
                'pos_order_id' => $data['pos_order_id'] ?? $appointment->pos_order_id,
                'invoice_id' => $data['invoice_id'] ?? $appointment->invoice_id,
            ]);

            if ($appointment->profile) {
                $appointment->profile->increment('lifetime_visits');
                $appointment->profile->increment('lifetime_spend', (float) $appointment->total);
                $appointment->profile->forceFill(['last_visit_at' => now()])->save();
                $this->earnLoyalty($appointment->profile, (float) $appointment->total, $appointment->appointment_number);
            }

            $this->createCommissions($appointment);

            return $appointment->fresh(['client', 'profile.loyaltyAccount', 'staff', 'services.service', 'commissions']);
        });
    }

    public function recordProductConsumption(Appointment $appointment, array $data): ProductConsumption
    {
        return DB::transaction(function () use ($appointment, $data) {
            $product = Product::findOrFail($data['product_id']);
            $quantity = (float) $data['quantity'];
            $unitCost = (float) ($data['unit_cost'] ?? $product->cost_price ?? 0);

            $consumption = ProductConsumption::create([
                'salon_appointment_id' => $appointment->id,
                'salon_service_id' => $data['salon_service_id'] ?? null,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit' => $data['unit'] ?? $product->stock_unit ?? 'pcs',
                'unit_cost' => $unitCost,
                'total_cost' => round($quantity * $unitCost, 2),
                'reference' => $data['reference'] ?? $appointment->appointment_number,
            ]);

            $this->stock->consume($product, $quantity, $consumption, $consumption->reference, 'Consumed by Salon & Spa appointment.');

            return $consumption->load('product');
        });
    }

    public function issueGiftCard(array $data): GiftCard
    {
        $amount = (float) $data['amount'];

        return GiftCard::create([
            'client_id' => $data['client_id'] ?? null,
            'card_number' => $data['card_number'] ?? $this->numbers->giftCard(),
            'initial_value' => $amount,
            'balance' => $amount,
            'currency' => $data['currency'] ?? 'KES',
            'expires_on' => $data['expires_on'] ?? null,
            'transactions' => [['type' => 'Issue', 'amount' => $amount, 'at' => now()->toISOString()]],
        ]);
    }

    private function earnLoyalty(ClientProfile $profile, float $amount, string $reference): void
    {
        $points = (int) floor($amount);
        $account = LoyaltyAccount::firstOrCreate(['salon_client_profile_id' => $profile->id]);
        $ledger = $account->ledger ?? [];
        $ledger[] = ['type' => 'Earn', 'points' => $points, 'reference' => $reference, 'at' => now()->toISOString()];

        $account->update([
            'points_balance' => $account->points_balance + $points,
            'lifetime_points' => $account->lifetime_points + $points,
            'last_activity_at' => now(),
            'ledger' => $ledger,
        ]);
    }

    private function linesToArray(?string $value): ?array
    {
        if (! $value) {
            return null;
        }

        return collect(preg_split('/\r\n|\r|\n/', $value))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    private function createCommissions(Appointment $appointment): void
    {
        foreach ($appointment->services as $line) {
            $staffId = $line->salon_staff_profile_id ?: $appointment->salon_staff_profile_id;
            if (! $staffId) {
                continue;
            }

            $rate = (float) ($line->service?->commission_rate ?: StaffProfile::find($staffId)?->commission_rate ?: 0);
            if ($rate <= 0) {
                continue;
            }

            Commission::firstOrCreate(
                ['salon_appointment_id' => $appointment->id, 'salon_staff_profile_id' => $staffId],
                [
                    'commission_date' => today(),
                    'base_amount' => $line->line_total,
                    'rate' => $rate,
                    'amount' => round(((float) $line->line_total * $rate) / 100, 2),
                ]
            );
        }
    }
}
