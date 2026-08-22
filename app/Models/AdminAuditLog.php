<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class AdminAuditLog extends Model{protected $fillable=['business_id','user_id','event','subject_type','subject_id','old_values','new_values','ip_address','user_agent'];protected $casts=['old_values'=>'array','new_values'=>'array'];}
