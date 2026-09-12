<?php

namespace Modules\Chama\Services;

use Illuminate\Support\Facades\DB;
use Modules\Chama\Models\TableBankingSession;

class TableBankingService
{
    public function __construct(
        private ChamaNumberService $numbers,
        private ChamaAuditService $audit,
    ) {}

    public function recordSession(array $data): TableBankingSession
    {
        return DB::transaction(function () use ($data) {
            $expected = $this->expectedClosing($data);
            $closing = (float) ($data['closing_balance'] ?? 0);
            $variance = round($closing - $expected, 2);

            $session = TableBankingSession::create(array_merge($data, [
                'session_number' => $data['session_number'] ?? $this->numbers->tableBankingSessionNumber(),
                'expected_closing_balance' => $expected,
                'variance' => $variance,
                'is_balanced' => abs($variance) <= 0.005,
                'status' => $data['status'] ?? (abs($variance) <= 0.005 ? 'Balanced' : 'Needs Review'),
            ]));

            $this->audit->record('table_banking.session.recorded', $session);

            return $session;
        });
    }

    public function expectedClosing(array $data): float
    {
        $opening = (float) ($data['opening_balance'] ?? 0);
        $inflows = (float) ($data['contributions'] ?? 0)
            + (float) ($data['savings'] ?? 0)
            + (float) ($data['loan_repayments'] ?? 0)
            + (float) ($data['interest'] ?? 0)
            + (float) ($data['fines'] ?? 0);
        $outflows = (float) ($data['expenses'] ?? 0) + (float) ($data['loans_disbursed'] ?? 0);

        return round($opening + $inflows - $outflows, 2);
    }
}
