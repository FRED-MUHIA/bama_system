<?php

namespace Modules\PrintingBranding\Models;

class FinishingOption extends PrintingBrandingModel
{
    protected $table = 'printing_finishing_options';

    protected $casts = ['is_active' => 'boolean'];
}
