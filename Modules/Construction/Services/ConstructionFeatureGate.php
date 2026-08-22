<?php

namespace Modules\Construction\Services;

class ConstructionFeatureGate
{
    public function authorize(string $permission): void
    {
        abort_unless(auth()->check() && auth()->user()->hasPermission($permission), 403);
    }
}
