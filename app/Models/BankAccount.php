<?php
namespace App\Models; use App\Models\Concerns\BelongsToBusiness; use Illuminate\Database\Eloquent\Model;
class BankAccount extends Model {use BelongsToBusiness;protected $fillable=['business_id','finance_account_id','name','type','institution','account_number','currency','opening_balance','is_active'];protected $casts=['opening_balance'=>'decimal:2','is_active'=>'boolean'];public function ledgerAccount(){return $this->belongsTo(FinanceAccount::class,'finance_account_id');}public function transactions(){return $this->hasMany(BankTransaction::class);}}
