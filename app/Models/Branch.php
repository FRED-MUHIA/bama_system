<?php
namespace App\Models;use App\Models\Concerns\BelongsToBusiness;use Illuminate\Database\Eloquent\Model;class Branch extends Model{use BelongsToBusiness;protected $fillable=['business_id','name','code','address','is_active'];protected $casts=['is_active'=>'boolean'];}
