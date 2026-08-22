<?php

namespace Modules\Fitness\Services;

use App\Services\ModuleRegistry;
use App\Services\SubscriptionManager;

class FitnessFeatureGate
{
    public function authorize(string $feature, int $increment = 1): void
    {
        abort_unless(app(ModuleRegistry::class)->enabledSlug('fitness'), 404, 'Fitness & Gym is not enabled for the active tenant.');
        abort_unless(app(SubscriptionManager::class)->allows('fitness.'.$feature, $increment), 402, 'Your current plan does not include this Fitness & Gym feature.');
    }
}
