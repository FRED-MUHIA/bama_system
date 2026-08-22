<?php

namespace Modules\Construction\Services;

use App\Models\Quotation;
use Illuminate\Support\Str;
use Modules\Construction\Models\ConstructionEstimate;
use Modules\Construction\Models\ConstructionTender;

class ConstructionEstimateService
{
    public function __construct(private ConstructionNumberService $numbers) {}

    public function create(array $data): ConstructionEstimate
    {
        $direct = (float) ($data['direct_cost'] ?? 0);
        $overhead = $direct * ((float) ($data['overhead_percentage'] ?? 0) / 100);
        $profit = ($direct + $overhead) * ((float) ($data['profit_percentage'] ?? 0) / 100);
        $tax = (float) ($data['tax'] ?? 0);

        return ConstructionEstimate::create([
            ...$data,
            'estimate_number' => $data['estimate_number'] ?? $this->numbers->next('EST', ConstructionEstimate::class, 'estimate_number'),
            'selling_price' => round($direct + $overhead + $profit + $tax, 2),
        ]);
    }

    public function convertToTender(ConstructionEstimate $estimate): ConstructionTender
    {
        $tender = ConstructionTender::create([
            'client_id' => $estimate->client_id,
            'project_id' => $estimate->project_id,
            'boq_id' => $estimate->boq_id,
            'estimate_id' => $estimate->id,
            'tender_number' => $this->numbers->next('TND', ConstructionTender::class, 'tender_number'),
            'name' => $estimate->title,
            'tender_value' => $estimate->selling_price,
            'status' => 'Preparing',
        ]);
        $estimate->update(['status' => 'Converted']);

        return $tender;
    }

    public function convertToQuotation(ConstructionEstimate $estimate): Quotation
    {
        $quotation = Quotation::create([
            'client_id' => $estimate->client_id,
            'project_id' => $estimate->project_id,
            'quotation_number' => $estimate->estimate_number,
            'quotation_date' => today(),
            'valid_until' => today()->addDays(30),
            'status' => 'draft',
            'subtotal' => $estimate->selling_price,
            'total' => $estimate->selling_price,
            'notes' => 'Generated from construction estimate '.$estimate->estimate_number,
            'public_token' => Str::random(40),
        ]);

        $estimate->update(['status' => 'Converted']);

        return $quotation;
    }
}
