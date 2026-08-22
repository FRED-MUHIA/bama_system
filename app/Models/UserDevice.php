<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class UserDevice extends Model{protected $fillable=['user_id','fingerprint','name','user_agent','ip_address','is_trusted','last_activity_at','revoked_at'];protected $casts=['is_trusted'=>'boolean','last_activity_at'=>'datetime','revoked_at'=>'datetime'];}
