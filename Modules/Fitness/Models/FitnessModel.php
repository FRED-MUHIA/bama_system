<?php

namespace Modules\Fitness\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

abstract class FitnessModel extends Model
{
    use BelongsToBusiness;
}
