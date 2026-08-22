<?php

namespace Modules\Salon\Repositories;

use Modules\Salon\Models\Appointment;
use Modules\Salon\Models\ClientProfile;
use Modules\Salon\Models\Service;
use Modules\Salon\Models\StaffProfile;

class SalonRepository
{
    public function activeServices(?string $query = null)
    {
        return Service::query()
            ->where('is_active', true)
            ->when($query, fn ($builder) => $builder->where('name', 'like', "%{$query}%"))
            ->orderBy('category')
            ->orderBy('name');
    }

    public function staff(?string $status = 'Active')
    {
        return StaffProfile::query()
            ->when($status, fn ($builder) => $builder->where('status', $status))
            ->orderBy('display_name');
    }

    public function clients(?string $query = null)
    {
        return ClientProfile::query()
            ->with('client', 'loyaltyAccount')
            ->when($query, function ($builder) use ($query) {
                $builder->where('client_code', 'like', "%{$query}%")
                    ->orWhereHas('client', fn ($client) => $client->where('name', 'like', "%{$query}%")->orWhere('phone', 'like', "%{$query}%"));
            })
            ->latest();
    }

    public function upcomingAppointments(int $limit = 12)
    {
        return Appointment::query()
            ->with('client', 'profile.client', 'staff', 'resource', 'services')
            ->whereDate('starts_at', '>=', today())
            ->orderBy('starts_at')
            ->limit($limit);
    }
}
