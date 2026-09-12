<?php

namespace Modules\Chama\Services;

use Illuminate\Support\Facades\DB;
use Modules\Chama\Models\WelfareRequest;

class WelfareService
{
    public function __construct(private ChamaNumberService $numbers, private ChamaAuditService $audit) {}

    public function request(array $data): WelfareRequest
    {
        return DB::transaction(function () use ($data) {
            $request = WelfareRequest::create(array_merge($data, [
                'request_number' => $data['request_number'] ?? $this->numbers->welfareNumber(),
                'status' => $data['status'] ?? 'Pending',
            ]));

            $this->audit->record('welfare.request.created', $request);

            return $request;
        });
    }

    public function approve(WelfareRequest $request, float $amount): WelfareRequest
    {
        $request->update([
            'amount_approved' => $amount,
            'status' => 'Approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        $this->audit->record('welfare.request.approved', $request);

        return $request->refresh();
    }
}
