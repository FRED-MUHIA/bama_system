<?php
namespace App\Models;use App\Models\Concerns\BelongsToBusiness;use Illuminate\Database\Eloquent\Model;
class BudgetAlert extends Model{use BelongsToBusiness;protected $fillable=['business_id','accounting_budget_id','actual_amount','utilization','severity','message','acknowledged_at','acknowledged_by'];protected $casts=['actual_amount'=>'decimal:2','utilization'=>'decimal:2','acknowledged_at'=>'datetime'];public function budget(){return $this->belongsTo(AccountingBudget::class,'accounting_budget_id');}}
