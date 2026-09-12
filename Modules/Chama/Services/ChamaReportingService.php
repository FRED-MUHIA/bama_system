<?php

namespace Modules\Chama\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Modules\Chama\Models\Contribution;
use Modules\Chama\Models\ContributionSchedule;
use Modules\Chama\Models\Fine;
use Modules\Chama\Models\Loan;
use Modules\Chama\Models\Member;
use Modules\Chama\Models\SavingsAccount;
use Modules\Chama\Models\TableBankingSession;

class ChamaReportingService
{
    public function csv(string $type): StreamedResponse
    {
        $rows = $this->rows($type);
        $headers = array_keys($rows[0] ?? ['message' => 'No records']);

        return response()->streamDownload(function () use ($rows, $headers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, array_map(fn ($key) => $row[$key] ?? null, $headers));
            }
            fclose($handle);
        }, 'chama-'.$type.'-'.now()->format('YmdHis').'.csv');
    }

    public function rows(string $type): array
    {
        return match ($type) {
            'members' => Member::latest()->get(['member_number', 'full_name', 'phone', 'status', 'membership_type', 'join_date'])->map->toArray()->all(),
            'contributions' => Contribution::with('member', 'contributionType')->latest()->get()->map(fn ($row) => [
                'member' => $row->member?->full_name,
                'type' => $row->contributionType?->name,
                'period' => $row->period,
                'amount_paid' => $row->amount_paid,
                'status' => $row->status,
            ])->all(),
            'arrears' => ContributionSchedule::with('member', 'contributionType')->where('balance', '>', 0)->get()->map(fn ($row) => [
                'member' => $row->member?->full_name,
                'type' => $row->contributionType?->name,
                'period' => $row->period,
                'due_date' => $row->due_date?->toDateString(),
                'balance' => $row->balance,
                'status' => $row->status,
            ])->all(),
            'savings' => SavingsAccount::with('member')->get()->map(fn ($row) => [
                'member' => $row->member?->full_name,
                'account' => $row->account_number,
                'type' => $row->account_type,
                'balance' => $row->current_balance,
                'status' => $row->status,
            ])->all(),
            'loans' => Loan::with('member', 'product')->get()->map(fn ($row) => [
                'member' => $row->member?->full_name,
                'loan' => $row->loan_number,
                'product' => $row->product?->name,
                'principal' => $row->principal,
                'outstanding' => (float) $row->outstanding_principal + (float) $row->outstanding_interest + (float) $row->outstanding_penalties,
                'status' => $row->status,
            ])->all(),
            'fines' => Fine::with('member')->get()->map(fn ($row) => [
                'member' => $row->member?->full_name,
                'type' => $row->fine_type,
                'amount' => $row->amount,
                'paid' => $row->amount_paid,
                'status' => $row->status,
            ])->all(),
            'table-banking' => TableBankingSession::latest('session_date')->get(['session_number', 'session_date', 'opening_balance', 'contributions', 'loan_repayments', 'interest', 'fines', 'expenses', 'loans_disbursed', 'closing_balance', 'variance', 'status'])->map->toArray()->all(),
            default => [['message' => 'Unsupported Chama report type']],
        };
    }
}
