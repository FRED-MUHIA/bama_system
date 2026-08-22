<?php

namespace Modules\PrintingBranding\Services;

class PrintingFeatureGate
{
    public function authorize(string $permission = 'printing.view'): void
    {
        abort_unless(auth()->check() && auth()->user()->hasPermission($permission), 403);
    }
}
