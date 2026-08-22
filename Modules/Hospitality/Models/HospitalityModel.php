<?php

namespace Modules\Hospitality\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

abstract class HospitalityModel extends Model
{
    use BelongsToBusiness;
}
