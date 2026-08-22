<?php

namespace Modules\Salon\Contracts;

use Modules\Salon\Models\Appointment;

interface SalonSpaServiceContract
{
    public function dashboard(): array;

    public function createService(array $data): \Modules\Salon\Models\Service;

    public function createClientProfile(array $data): \Modules\Salon\Models\ClientProfile;

    public function createStaffProfile(array $data): \Modules\Salon\Models\StaffProfile;

    public function createMembershipPlan(array $data): \Modules\Salon\Models\MembershipPlan;

    public function enrollMembership(array $data): \Modules\Salon\Models\Membership;

    public function awardLoyaltyPoints(\Modules\Salon\Models\ClientProfile $profile, int $points, string $reason, ?string $reference = null): \Modules\Salon\Models\LoyaltyAccount;

    public function issueGiftCard(array $data): \Modules\Salon\Models\GiftCard;

    public function createConsultation(array $data): \Modules\Salon\Models\Consultation;

    public function createTreatment(array $data): \Modules\Salon\Models\Treatment;

    public function createWellnessProgram(array $data): \Modules\Salon\Models\WellnessProgram;

    public function enrollWellnessProgram(array $data): \Modules\Salon\Models\WellnessEnrollment;

    public function updateCommissionStatus(\Modules\Salon\Models\Commission $commission, string $status): \Modules\Salon\Models\Commission;

    public function bookAppointment(array $data): Appointment;

    public function completeAppointment(Appointment $appointment, array $data = []): Appointment;

    public function recordProductConsumption(Appointment $appointment, array $data): \Modules\Salon\Models\ProductConsumption;
}
