<?php

namespace Modules\Salon\Services;

use App\Services\ModuleRegistry;

class SalonFeatureGate
{
    public function authorize(string $feature = 'view'): void
    {
        abort_unless(app(ModuleRegistry::class)->enabledSlug('salon'), 404, 'Salon & Spa is not enabled for the active tenant.');

        $permission = match ($feature) {
            'appointments' => 'salon.appointments.view',
            'staff' => 'salon.staff.view',
            'services' => 'salon.services.view',
            'pos' => 'salon.pos.view',
            'memberships' => 'salon.memberships.view',
            'loyalty' => 'salon.loyalty.view',
            'reports' => 'salon.reports',
            default => 'salon.view',
        };

        abort_unless(auth()->check() && auth()->user()->hasPermission($permission), 403);
    }
}
