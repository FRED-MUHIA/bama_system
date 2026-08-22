<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class TermsCondition extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'title', 'content', 'is_default'];
    protected $casts = ['is_default' => 'boolean'];
}
