<?php

namespace Modules\Construction\Services;

use App\Models\Invoice;
use Illuminate\Support\Str;
use Modules\Construction\Models\ConstructionCertificate;
use Modules\Construction\Models\ConstructionProgressMeasurement;
use Modules\Construction\Models\ConstructionVariation;

class CommercialService
{
    public function __construct(private ConstructionNumberService $numbers) {}

    public function measurement(array $data): ConstructionProgressMeasurement
    {
        return ConstructionProgressMeasurement::create([
            ...$data,
            'measurement_number' => $data['measurement_number'] ?? $this->numbers->next('MSR', ConstructionProgressMeasurement::class, 'measurement_number'),
        ]);
    }

    public function certificate(array $data): ConstructionCertificate
    {
        $gross = (float) ($data['gross_certified'] ?? (
            (float) ($data['work_executed'] ?? 0)
            + (float) ($data['materials_on_site'] ?? 0)
            + (float) ($data['approved_variations'] ?? 0)
        ));
        $retention = (float) ($data['retention'] ?? 0);
        $advance = (float) ($data['advance_recovery'] ?? 0);
        $previous = (float) ($data['previous_certificates'] ?? 0);
        $tax = (float) ($data['tax'] ?? 0);

        return ConstructionCertificate::create([
            ...$data,
            'certificate_number' => $data['certificate_number'] ?? $this->numbers->next('IPC', ConstructionCertificate::class, 'certificate_number'),
            'gross_certified' => $gross,
            'net_certificate' => round($gross - $retention - $advance - $previous + $tax, 2),
        ]);
    }

    public function variation(array $data): ConstructionVariation
    {
        return ConstructionVariation::create([
            ...$data,
            'variation_number' => $data['variation_number'] ?? $this->numbers->next('VO', ConstructionVariation::class, 'variation_number'),
        ]);
    }

    public function invoiceCertificate(ConstructionCertificate $certificate): Invoice
    {
        $invoice = Invoice::create([
            'client_id' => $certificate->client_id,
            'project_id' => $certificate->project_id,
            'invoice_number' => 'INV-'.$certificate->certificate_number,
            'public_token' => Str::random(40),
            'invoice_date' => today(),
            'due_date' => today()->addDays(14),
            'payment_status' => 'Unpaid',
            'subtotal' => $certificate->net_certificate,
            'tax_total' => $certificate->tax,
            'total' => $certificate->net_certificate,
            'amount_paid' => 0,
            'balance' => $certificate->net_certificate,
            'industry_module' => 'construction',
            'industry_reference' => $certificate->certificate_number,
            'industry_context' => [
                'certificate_id' => $certificate->id,
                'certificate_number' => $certificate->certificate_number,
                'contract_value' => $certificate->contract_value,
                'gross_certified' => $certificate->gross_certified,
                'retention' => $certificate->retention,
                'net_certificate' => $certificate->net_certificate,
            ],
            'notes' => 'Construction interim payment certificate '.$certificate->certificate_number,
        ]);

        $certificate->update(['invoice_id' => $invoice->id, 'status' => 'Invoiced']);

        return $invoice;
    }
}
