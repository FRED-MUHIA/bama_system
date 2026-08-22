<?php
namespace App\Models;use App\Models\Concerns\BelongsToBusiness;use Illuminate\Database\Eloquent\Model;class Team extends Model{use BelongsToBusiness;protected $fillable=['business_id','name','type','manager_id','is_active'];public function users(){return $this->belongsToMany(User::class);}public function manager(){return $this->belongsTo(User::class,'manager_id');}}
