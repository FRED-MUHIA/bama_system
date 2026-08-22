<?php

namespace Modules\Construction\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

abstract class ConstructionModel extends Model
{
    use BelongsToBusiness;

    protected $guarded = ['id', 'tenant_id', 'business_id', 'created_at', 'updated_at'];
}
