<?php

namespace Modules\Salon\Services;

use App\Models\Client;
use App\Models\Product;
use Illuminate\Validation\Rule;
use Modules\Salon\Models as Models;

/** Explicit editable fields shared by the record forms and server validation. */
class SalonRecords
{
    public static function definition(string $type): array
    {
        $text = fn ($label, $required = false) => ['label' => $label, 'type' => 'text', 'rules' => [$required ? 'required' : 'nullable', 'string', 'max:255']];
        $notes = fn ($label, $lines = false) => ['label' => $label, 'type' => 'textarea', 'lines' => $lines, 'rules' => ['nullable', 'string', 'max:20000']];
        $number = fn ($label, $min = 0, $max = 999999999, $integer = false) => ['label' => $label, 'type' => 'number', 'min' => $min, 'max' => $max, 'step' => $integer ? '1' : '0.01', 'rules' => ['required', $integer ? 'integer' : 'numeric', 'min:'.$min, 'max:'.$max]];
        $select = fn ($label, $options) => ['label' => $label, 'type' => 'select', 'options' => $options, 'rules' => ['required', Rule::in(array_keys($options))]];
        $status = fn ($values) => $select('Status', array_combine($values, $values));
        $active = $select('Availability', [1 => 'Active', 0 => 'Inactive']);
        $date = fn ($label, $required = false) => ['label' => $label, 'type' => 'date', 'rules' => [$required ? 'required' : 'nullable', 'date']];
        $related = fn ($label, $model, $required = false) => ['label' => $label, 'type' => 'relation', 'model' => $model, 'rules' => [$required ? 'required' : 'nullable', 'integer', self::exists($model)]];
        $profile = $related('Client', Models\ClientProfile::class, true);
        $staff = $related('Staff member', Models\StaffProfile::class);
        $appointment = $related('Appointment', Models\Appointment::class);
        $dates = ['starts_on' => $date('Starts on', true), 'ends_on' => $date('Ends on')];
        $definitions = [
            'clients' => [Models\ClientProfile::class, 'loyalty', 'clients', 'Client profile', [
                'name' => $text('Client name', true), 'phone' => $text('Phone'),
                'email' => ['label' => 'Email', 'type' => 'email', 'rules' => ['nullable', 'email', 'max:255']],
                'date_of_birth' => $date('Date of birth'), 'gender' => $text('Gender'),
                'preferences' => $notes('Preferences (one per line)', true), 'allergies' => $notes('Allergies (one per line)', true),
                'skin_hair_profile' => $notes('Skin / hair profile (one per line)', true), 'status' => $status(['Active', 'Inactive']),
            ]],
            'staff' => [Models\StaffProfile::class, 'staff', 'staff', 'Staff profile', [
                'display_name' => $text('Display name', true), 'role_title' => $text('Role title'),
                'specialties' => $notes('Specialties (one per line)', true), 'commission_rate' => $number('Commission %', 0, 100),
                'hourly_rate' => $number('Hourly rate'), 'weekly_capacity_minutes' => $number('Weekly capacity (minutes)', 0, 10080, true),
                'status' => $status(['Active', 'Inactive']),
            ]],
            'services' => [Models\Service::class, 'services', 'services', 'Service', [
                'name' => $text('Service name', true), 'category' => $text('Category'), 'description' => $notes('Description'),
                'duration_minutes' => $number('Duration (minutes)', 5, 1440, true), 'price' => $number('Price'),
                'tax_rate' => $number('Tax %', 0, 100), 'commission_rate' => $number('Commission %', 0, 100),
                'requires_consultation' => $select('Consultation required', [0 => 'No', 1 => 'Yes']), 'is_active' => $active,
            ]],
            'appointments' => [Models\Appointment::class, 'appointments', 'appointments', 'Appointment', [
                'salon_staff_profile_id' => $staff, 'salon_resource_id' => $related('Chair / room', Models\Resource::class),
                'starts_at' => ['label' => 'Starts at', 'type' => 'datetime-local', 'rules' => ['required', 'date']],
                'channel' => $text('Booking channel', true), 'notes' => $notes('Notes'),
                'status' => $status(['Booked', 'Confirmed', 'Arrived', 'In Progress', 'Cancelled', 'No Show']),
            ]],
            'membership-plans' => [Models\MembershipPlan::class, 'memberships', 'memberships', 'Membership plan', [
                'name' => $text('Plan name', true), 'billing_cycle' => $select('Billing cycle', array_combine(['Weekly', 'Monthly', 'Quarterly', 'Annual'], ['Weekly', 'Monthly', 'Quarterly', 'Annual'])),
                'price' => $number('Price'), 'visit_allowance' => ['label' => 'Visit allowance (blank for unlimited)', 'type' => 'number', 'min' => 0, 'step' => 1, 'rules' => ['nullable', 'integer', 'min:0']],
                'discount_rate' => $number('Discount %', 0, 100), 'benefits' => $notes('Benefits (one per line)', true), 'is_active' => $active,
            ]],
            'memberships' => [Models\Membership::class, 'memberships', 'memberships', 'Membership', $dates + [
                'visits_remaining' => ['label' => 'Visits remaining (blank for unlimited)', 'type' => 'number', 'min' => 0, 'step' => 1, 'rules' => ['nullable', 'integer', 'min:0']],
                'status' => $status(['Active', 'Paused', 'Expired', 'Cancelled']),
            ]],
            'consultations' => [Models\Consultation::class, 'consultations', 'consultations', 'Consultation', [
                'salon_client_profile_id' => $profile, 'salon_staff_profile_id' => $staff, 'salon_appointment_id' => $appointment,
                'consultation_type' => $text('Consultation type', true), 'observations' => $notes('Observations (one per line)', true),
                'recommendations' => $notes('Recommendations (one per line)', true), 'contraindications' => $notes('Contraindications (one per line)', true),
                'follow_up_date' => $date('Follow-up date'),
            ]],
            'treatments' => [Models\Treatment::class, 'treatments', 'treatments', 'Treatment', [
                'name' => $text('Treatment name', true), 'salon_client_profile_id' => $profile, 'salon_staff_profile_id' => $staff,
                'salon_appointment_id' => $appointment, 'salon_service_id' => $related('Service', Models\Service::class),
                'performed_on' => $date('Performed on', true), 'notes' => $notes('Notes'),
                'products_used' => $notes('Products used (one per line)', true), 'aftercare' => $notes('Aftercare (one per line)', true),
            ]],
            'gift-cards' => [Models\GiftCard::class, 'loyalty', 'loyalty', 'Gift card', [
                'expires_on' => $date('Expires on'), 'status' => $status(['Active', 'Suspended', 'Void']),
            ]],
            'loyalty' => [Models\LoyaltyAccount::class, 'loyalty', 'loyalty', 'Loyalty account', ['tier' => $text('Tier', true)]],
            'consumptions' => [Models\ProductConsumption::class, 'inventory', 'inventory', 'Product usage', [
                'quantity' => ['label' => 'Quantity', 'type' => 'number', 'step' => '0.001', 'min' => '0.001', 'rules' => ['required', 'numeric', 'min:0.001']],
                'unit' => $text('Unit', true), 'unit_cost' => $number('Unit cost'),
            ]],
            'commissions' => [Models\Commission::class, 'commissions', 'commissions', 'Commission', [
                'commission_date' => $date('Commission date', true), 'base_amount' => $number('Base amount'), 'rate' => $number('Rate %', 0, 100),
            ]],
            'programs' => [Models\WellnessProgram::class, 'wellness', 'wellness', 'Wellness program', [
                'name' => $text('Program name', true), 'category' => $text('Category'), 'description' => $notes('Description'),
                'duration_days' => $number('Duration (days)', 1, 3650, true), 'price' => $number('Price'),
                'milestones' => $notes('Milestones (one per line)', true), 'is_active' => $active,
            ]],
            'enrollments' => [Models\WellnessEnrollment::class, 'wellness', 'wellness', 'Wellness enrollment', $dates + [
                'progress' => $notes('Progress (one per line)', true), 'status' => $status(['Active', 'Completed', 'Cancelled']),
            ]],
            'resources' => [Models\Resource::class, 'appointments', 'appointments', 'Chair / room', [
                'name' => $text('Name', true), 'type' => $select('Type', ['Chair' => 'Chair', 'Room' => 'Room', 'Equipment' => 'Equipment']),
                'capacity' => $number('Capacity', 1, 100, true), 'status' => $status(['Available', 'Maintenance', 'Inactive']),
                'equipment' => $notes('Equipment (one per line)', true),
            ]],
            'schedules' => [Models\StaffSchedule::class, 'staff', 'staff', 'Staff shift', [
                'salon_staff_profile_id' => $related('Staff member', Models\StaffProfile::class, true), 'work_date' => $date('Work date', true),
                'starts_at' => ['label' => 'Starts at', 'type' => 'time', 'rules' => ['required', 'date_format:H:i']],
                'ends_at' => ['label' => 'Ends at', 'type' => 'time', 'rules' => ['required', 'date_format:H:i', 'after:starts_at']],
                'shift_type' => $text('Shift type', true), 'capacity_minutes' => $number('Capacity (minutes)', 1, 1440, true),
                'status' => $status(['Scheduled', 'Completed', 'Cancelled']),
            ]],
            'packages' => [Models\Package::class, 'services', 'services', 'Service package', [
                'name' => $text('Package name', true), 'price' => $number('Price'), 'valid_days' => $number('Valid for (days)', 1, 3650, true),
                'service_ids' => ['label' => 'Included services', 'type' => 'multiple', 'model' => Models\Service::class, 'rules' => ['required', 'array', 'min:1']],
                'benefits' => $notes('Benefits (one per line)', true), 'is_active' => $active,
            ]],
        ];
        abort_unless(isset($definitions[$type]), 404);
        [$model, $permission, $index, $label, $fields] = $definitions[$type];

        return compact('model', 'permission', 'index', 'label', 'fields');
    }

    public static function exists(string $model): \Closure
    {
        return function ($attribute, $value, $fail) use ($model) {
            if (! $model::whereKey($value)->exists()) {
                $fail('The selected '.str_replace('_', ' ', $attribute).' is unavailable in this business.');
            }
        };
    }

    public static function label($record): string
    {
        return (string) ($record->name ?? $record->display_name ?? $record->appointment_number
            ?? ($record instanceof Models\ClientProfile ? $record->client?->name : null) ?? 'Record #'.$record->id);
    }
}
