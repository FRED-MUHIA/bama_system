<?php

namespace Modules\Chama\Services;

use Modules\Chama\Models\Loan;
use Modules\Chama\Models\LoanRepayment;

class LoanRepaymentService
{
    public function __construct(private ChamaLoanService $loans) {}

    public function record(Loan $loan, array $data): LoanRepayment
    {
        return $this->loans->recordRepayment($loan, $data);
    }
}
