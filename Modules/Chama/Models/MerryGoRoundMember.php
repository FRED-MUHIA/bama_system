<?php

namespace Modules\Chama\Models;

class MerryGoRoundMember extends ChamaModel
{
    protected $table = 'chama_merry_go_round_members';

    public function cycle() { return $this->belongsTo(MerryGoRoundCycle::class, 'cycle_id'); }
    public function member() { return $this->belongsTo(Member::class); }
}
