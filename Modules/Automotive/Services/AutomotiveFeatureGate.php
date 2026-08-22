<?php

namespace Modules\Automotive\Services;

class AutomotiveFeatureGate
{
    public function authorize(string $permission): void
    {
        abort_unless(auth()->check() && auth()->user()->hasPermission($permission), 403);
    }
}
