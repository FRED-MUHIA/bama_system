<?php

namespace Modules\Salon\Services;

use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Modules\Salon\Models\Appointment;
use Modules\Salon\Models\Resource;
use Modules\Salon\Models\StaffProfile;

class SalonBooking
{
    /** Call inside the booking transaction so staff/resource locks serialize bookings. */
    public function ensureAvailable(array $data, ?int $except = null, array $lineStaff = []): void
    {
        $start = Carbon::parse($data['starts_at']);
        $end = Carbon::parse($data['ends_at']);
        if ($end->lessThanOrEqualTo($start)) {
            throw ValidationException::withMessages(['starts_at' => 'The appointment must end after it starts.']);
        }
        $overlapping = fn () => Appointment::whereNotIn('status', ['Cancelled', 'No Show'])
            ->when($except, fn ($query) => $query->where('id', '!=', $except))
            ->where('starts_at', '<', $end)->where('ends_at', '>', $start);
        $staffIds = array_values(array_unique(array_filter([...$lineStaff, $data['salon_staff_profile_id'] ?? null])));
        sort($staffIds);
        foreach ($staffIds as $staffId) {
            $staff = StaffProfile::whereKey($staffId)->lockForUpdate()->firstOrFail();
            $busy = $overlapping()->where(fn ($query) => $query->where('salon_staff_profile_id', $staffId)
                ->orWhereHas('services', fn ($line) => $line->where('salon_staff_profile_id', $staffId)))->exists();
            if ($staff->status !== 'Active' || $busy) {
                throw ValidationException::withMessages(['salon_staff_profile_id' => $staff->display_name.' is unavailable at this time.']);
            }
            $shifts = $staff->schedules()->whereDate('work_date', $start)->where('status', 'Scheduled')->get();
            if ($shifts->isNotEmpty() && ! $shifts->contains(fn ($shift) => $start->isSameDay($end)
                && $shift->starts_at <= $start->format('H:i:s') && $shift->ends_at >= $end->format('H:i:s'))) {
                throw ValidationException::withMessages(['starts_at' => 'Choose a time within '.$staff->display_name.'’s scheduled shift.']);
            }
        }
        if (! empty($data['salon_resource_id'])) {
            $resource = Resource::whereKey($data['salon_resource_id'])->lockForUpdate()->firstOrFail();
            if ($resource->status !== 'Available' || $overlapping()->where('salon_resource_id', $resource->id)->count() >= $resource->capacity) {
                throw ValidationException::withMessages(['salon_resource_id' => 'This chair or room is unavailable at the selected time.']);
            }
        }
    }
}
