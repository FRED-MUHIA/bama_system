<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class AssetDepreciationSchedule extends Model {protected $fillable=['fixed_asset_id','journal_entry_id','period_date','opening_value','depreciation','closing_value','status'];protected $casts=['period_date'=>'date','opening_value'=>'decimal:2','depreciation'=>'decimal:2','closing_value'=>'decimal:2'];}
