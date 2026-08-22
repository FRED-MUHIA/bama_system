<?php

namespace Modules\PrintingBranding\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

abstract class PrintingBrandingModel extends Model
{
    use BelongsToBusiness;

    protected $guarded = ['id', 'tenant_id', 'business_id', 'created_at', 'updated_at'];
}
