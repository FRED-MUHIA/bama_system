<?php

namespace Modules\Chama\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Chama\Models\Loan;
use Modules\Chama\Models\LoanGuarantor;
use Modules\Chama\Models\Member;

class GuarantorService
{
    public function __construct(private ChamaAuditService $audit) {}

    public function add(Loan $loan, Member $guarantor, float $amount, ?float $exposureLimit = null): LoanGuarantor
    {
        return DB::transaction(function () use ($loan, $guarantor, $amount, $exposureLimit) {
            $currentExposure = (float) $guarantor->guaranteedLoans()->whereIn('status', ['Pending', 'Confirmed'])->sum('exposure_amount');

            if ($exposureLimit !== null && ($currentExposure + $amount) > $exposureLimit) {
                throw ValidationException::withMessages(['guarantor' => 'This guarantor would exceed the configured exposure limit.']);
            }

            $record = LoanGuarantor::create([
                'loan_id' => $loan->id,
                'guarantor_member_id' => $guarantor->id,
                'guaranteed_amount' => $amount,
                'exposure_amount' => $amount,
                'status' => 'Pending',
            ]);

            $this->audit->record('loan.guarantor.added', $record);

            return $record;
        });
    }

    public function confirm(LoanGuarantor $guarantor): LoanGuarantor
    {
        $guarantor->update(['status' => 'Confirmed', 'confirmed_at' => now()]);
        $this->audit->record('loan.guarantor.confirmed', $guarantor);

        return $guarantor->refresh();
    }
}
