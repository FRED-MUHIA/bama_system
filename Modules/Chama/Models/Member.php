<?php

namespace Modules\Chama\Models;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends ChamaModel
{
    use SoftDeletes;

    public const STATUSES = ['Applicant', 'Active', 'Suspended', 'Inactive', 'Exited', 'Deceased'];

    protected $table = 'chama_members';

    protected $casts = [
        'date_of_birth' => 'date',
        'join_date' => 'date',
        'next_of_kin' => 'array',
        'kyc_verified_at' => 'datetime',
        'constitution_accepted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function client() { return $this->belongsTo(Client::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function savingsAccounts() { return $this->hasMany(SavingsAccount::class); }
    public function contributionSchedules() { return $this->hasMany(ContributionSchedule::class); }
    public function contributions() { return $this->hasMany(Contribution::class); }
    public function loans() { return $this->hasMany(Loan::class); }
    public function guaranteedLoans() { return $this->hasMany(LoanGuarantor::class, 'guarantor_member_id'); }
    public function fines() { return $this->hasMany(Fine::class); }
    public function welfareRequests() { return $this->hasMany(WelfareRequest::class); }
    public function shareTransactions() { return $this->hasMany(ShareTransaction::class); }
    public function dividendAllocations() { return $this->hasMany(DividendAllocation::class); }
    public function attendance() { return $this->hasMany(Attendance::class); }
    public function votes() { return $this->hasMany(Vote::class); }
}
