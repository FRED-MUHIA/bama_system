<?php

namespace Modules\Automotive\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

abstract class AutomotiveModel extends Model
{
    use BelongsToBusiness;

    protected $guarded = ['id', 'tenant_id', 'business_id'];
}
