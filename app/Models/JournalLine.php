<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class JournalLine extends Model {protected $fillable=['journal_entry_id','finance_account_id','department_id','cost_center_id','project_id','description','debit','credit'];protected $casts=['debit'=>'decimal:2','credit'=>'decimal:2'];public function entry(){return $this->belongsTo(JournalEntry::class,'journal_entry_id');}public function account(){return $this->belongsTo(FinanceAccount::class,'finance_account_id');}}
