<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class IamRole extends Model{protected $fillable=['business_id','name','slug','description','landing_route','is_system'];protected $casts=['is_system'=>'boolean'];public function permissions(){return $this->belongsToMany(IamPermission::class,'iam_permission_role');}}
