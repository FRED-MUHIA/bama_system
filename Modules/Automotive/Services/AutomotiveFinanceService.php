<?php

namespace Modules\Automotive\Services;

use App\Models\Invoice;
use Illuminate\Support\Str;
use Modules\Automotive\Models\JobCard;
use Modules\Automotive\Models\JobCost;

class AutomotiveFinanceService
{
    public function invoiceJob(JobCard $job): Invoice
    {
        $estimate = $job->estimates()->latest()->first();
        $labour = (float) ($estimate?->labour_total ?? $job->labourTasks()->sum('line_total'));
        $parts = (float) ($estimate?->parts_total ?? $job->partRequests()->with('items')->get()->sum(fn ($request) => $request->items->sum(fn ($item) => (float) $item->issued_qty * (float) $item->unit_price)));
        $total = (float) ($estimate?->total ?? ($labour + $parts));

        $invoice = Invoice::create([
            'client_id' => $job->client_id,
            'invoice_number' => 'INV-'.$job->job_number,
            'public_token' => Str::random(40),
            'invoice_date' => today(),
            'due_date' => today()->addDays(7),
            'payment_status' => 'unpaid',
            'subtotal' => $total,
            'tax_total' => $estimate?->tax_total ?? 0,
            'total' => $total,
            'amount_paid' => 0,
            'balance' => $total,
            'industry_module' => 'automotive',
            'industry_reference' => $job->job_number,
            'industry_context' => [
                'job_card_id' => $job->id,
                'vehicle_id' => $job->vehicle_id,
                'registration_number' => $job->vehicle?->registration_number,
                'labour_revenue' => $labour,
                'parts_revenue' => $parts,
            ],
            'notes' => 'Automotive job card invoice '.$job->job_number,
        ]);

        $job->update(['invoice_id' => $invoice->id]);

        return $invoice;
    }

    public function costing(JobCard $job, array $data = []): JobCost
    {
        $partsCost = (float) ($data['parts_cost'] ?? $job->partRequests()->with('items')->get()->sum(fn ($request) => $request->items->sum(fn ($item) => (float) $item->issued_qty * (float) $item->unit_cost)));
        $labourCost = (float) ($data['labour_cost'] ?? $job->labourTasks()->get()->sum(fn ($task) => (float) $task->actual_hours * ((float) $task->hourly_rate * 0.5)));
        $other = (float) ($data['consumables_cost'] ?? 0) + (float) ($data['outsourced_cost'] ?? 0) + (float) ($data['transport_cost'] ?? 0) + (float) ($data['other_cost'] ?? 0);
        $actual = $partsCost + $labourCost + $other;
        $revenue = (float) ($data['revenue'] ?? $job->invoice?->total ?? 0);
        $profit = $revenue - $actual;

        return JobCost::updateOrCreate(
            ['job_card_id' => $job->id],
            [
                'parts_cost' => $partsCost,
                'labour_cost' => $labourCost,
                'technician_cost' => $data['technician_cost'] ?? $labourCost,
                'consumables_cost' => $data['consumables_cost'] ?? 0,
                'outsourced_cost' => $data['outsourced_cost'] ?? 0,
                'transport_cost' => $data['transport_cost'] ?? 0,
                'other_cost' => $data['other_cost'] ?? 0,
                'actual_cost' => $actual,
                'revenue' => $revenue,
                'gross_profit' => $profit,
                'margin_percentage' => $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0,
            ]
        );
    }
}
