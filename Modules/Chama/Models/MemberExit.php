<?php

namespace Modules\Chama\Models;

class MemberExit extends ChamaModel
{
    protected $table = 'chama_member_exits';

    protected $casts = [
        'reported_date' => 'date',
        'effective_date' => 'date',
        'next_of_kin' => 'array',
        'supporting_documents' => 'array',
        'approved_at' => 'datetime',
    ];

    public function member() { return $this->belongsTo(Member::class); }
}
