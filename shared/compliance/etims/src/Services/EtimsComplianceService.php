<?php

namespace Shared\Compliance\Etims\Services;

use App\Models\PosOrder;
use App\Services\IamService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Shared\Compliance\Etims\Contracts\EtimsComplianceServiceContract;
use Shared\Compliance\Etims\Models\EtimsAuditLog;
use Shared\Compliance\Etims\Models\EtimsSubmission;

class EtimsComplianceService implements EtimsComplianceServiceContract
{
    public function __construct(private IamService $iam)
    {
    }

    public function submitSale(PosOrder $order, array $context = []): EtimsSubmission
    {
        $order->loadMissing('items.product', 'payments.paymentMethod', 'client');

        return $this->submitFiscalDocument([
            'source' => $order,
            'industry' => $context['industry'] ?? 'retail',
            'document_type' => 'Fiscal Invoice',
            'document_number' => $order->order_number,
            'customer' => [
                'name' => $order->client?->name ?: $order->customer_name,
                'phone' => $order->client?->phone ?: $order->customer_phone,
                'email' => $order->client?->email ?: $order->customer_email,
            ],
            'currency' => $context['currency'] ?? 'KES',
            'subtotal' => (float) $order->subtotal,
            'discount_total' => (float) $order->discount_total,
            'tax_total' => (float) $order->tax_total,
            'total' => (float) $order->total,
            'amount_paid' => (float) $order->amount_paid,
            'items' => $order->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'sku' => $item->product?->sku,
                'name' => $item->title,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount' => (float) $item->discount,
                'tax_rate' => (float) $item->tax_rate,
                'line_total' => (float) $item->line_total,
            ])->values()->all(),
            'payments' => $order->payments->map(fn ($payment) => [
                'method' => $payment->paymentMethod?->type ?: $payment->paymentMethod?->name,
                'amount' => (float) $payment->amount,
                'reference' => $payment->reference,
            ])->values()->all(),
        ], $context);
    }

    public function submitFiscalDocument(array $document, array $context = []): EtimsSubmission
    {
        return DB::transaction(function () use ($document, $context) {
            $source = $document['source'] ?? null;
            $payload = $this->payload($document, $context);
            $offline = (bool) ($context['offline'] ?? false);
            $forceFail = (bool) ($context['force_fail'] ?? false);
            $documentType = $document['document_type'] ?? 'Fiscal Invoice';
            $industry = $document['industry'] ?? $context['industry'] ?? null;

            $attributes = [
                'industry' => $industry,
                'payload' => $payload,
                'status' => $offline ? EtimsSubmission::STATUS_OFFLINE : EtimsSubmission::STATUS_PENDING,
                'next_retry_at' => $offline ? now()->addMinutes(5) : null,
                'last_error' => null,
            ];

            $submission = $source instanceof Model
                ? EtimsSubmission::updateOrCreate(
                    [
                        'source_type' => $source->getMorphClass(),
                        'source_id' => $source->getKey(),
                        'document_type' => $documentType,
                    ],
                    $attributes
                )
                : EtimsSubmission::create($attributes + [
                    'document_type' => $documentType,
                ]);

            $this->audit($submission, 'queued', $submission->status, 'Fiscal document queued for ETIMS.', $payload);

            if (! $offline) {
                $this->transmit($submission, $forceFail);
            }

            if ($source instanceof Model) {
                $this->iam->audit('etims.submission.'.$submission->status, $source);
            }

            return $submission->refresh();
        });
    }

    public function queueCreditNote(Model $source, array $document, array $context = []): EtimsSubmission
    {
        return $this->submitFiscalDocument($document + ['source' => $source, 'document_type' => 'Credit Note'], $context);
    }

    public function queueDebitNote(Model $source, array $document, array $context = []): EtimsSubmission
    {
        return $this->submitFiscalDocument($document + ['source' => $source, 'document_type' => 'Debit Note'], $context);
    }

    public function retryPending(int $limit = 50): Collection
    {
        return EtimsSubmission::query()
            ->whereIn('status', [EtimsSubmission::STATUS_PENDING, EtimsSubmission::STATUS_OFFLINE, EtimsSubmission::STATUS_FAILED])
            ->where(fn ($query) => $query->whereNull('next_retry_at')->orWhere('next_retry_at', '<=', now()))
            ->oldest()
            ->limit($limit)
            ->get()
            ->map(fn (EtimsSubmission $submission) => $this->transmit($submission));
    }

    public function metrics(?string $industry = null): array
    {
        $query = EtimsSubmission::query()->when($industry, fn ($query) => $query->where('industry', $industry));
        $total = (clone $query)->count();
        $validated = (clone $query)->where('status', EtimsSubmission::STATUS_VALIDATED)->count();

        return [
            'submitted_invoices' => (clone $query)->whereIn('status', [EtimsSubmission::STATUS_SUBMITTED, EtimsSubmission::STATUS_VALIDATED])->where('document_type', 'Fiscal Invoice')->count(),
            'pending_invoices' => (clone $query)->whereIn('status', [EtimsSubmission::STATUS_PENDING, EtimsSubmission::STATUS_OFFLINE])->where('document_type', 'Fiscal Invoice')->count(),
            'failed_submissions' => (clone $query)->where('status', EtimsSubmission::STATUS_FAILED)->count(),
            'credit_notes' => (clone $query)->where('document_type', 'Credit Note')->count(),
            'debit_notes' => (clone $query)->where('document_type', 'Debit Note')->count(),
            'compliance_rate' => $total ? round(($validated / $total) * 100, 2) : 100.0,
        ];
    }

    private function transmit(EtimsSubmission $submission, bool $forceFail = false): EtimsSubmission
    {
        $submission->increment('attempt_count');

        if ($forceFail) {
            $submission->update([
                'status' => EtimsSubmission::STATUS_FAILED,
                'last_error' => 'ETIMS provider rejected the fiscal document during validation.',
                'next_retry_at' => now()->addMinutes(10),
            ]);
            $this->audit($submission, 'submission-failed', EtimsSubmission::STATUS_FAILED, $submission->last_error);

            return $submission->refresh();
        }

        $invoiceNumber = $submission->fiscal_invoice_number ?: $this->number('INV', $submission);
        $receiptNumber = $submission->fiscal_receipt_number ?: $this->number('RCT', $submission);
        $qrPayload = $this->qrPayload($submission, $invoiceNumber, $receiptNumber);

        $submission->update([
            'status' => EtimsSubmission::STATUS_VALIDATED,
            'fiscal_invoice_number' => $invoiceNumber,
            'fiscal_receipt_number' => $receiptNumber,
            'qr_code' => $qrPayload,
            'validation_result' => [
                'valid' => true,
                'validated_at' => now()->toIso8601String(),
                'tax_total' => data_get($submission->payload, 'tax_total', 0),
            ],
            'submitted_at' => $submission->submitted_at ?: now(),
            'validated_at' => now(),
            'next_retry_at' => null,
            'last_error' => null,
        ]);

        $this->audit($submission, 'validated', EtimsSubmission::STATUS_VALIDATED, 'ETIMS fiscal document validated.', [
            'fiscal_invoice_number' => $invoiceNumber,
            'fiscal_receipt_number' => $receiptNumber,
            'qr_code' => $qrPayload,
        ]);

        return $submission->refresh();
    }

    private function payload(array $document, array $context): array
    {
        unset($document['source']);

        return array_filter($document + [
            'submission_mode' => ($context['offline'] ?? false) ? 'offline' : 'online',
            'created_at' => now()->toIso8601String(),
        ], fn ($value) => $value !== null);
    }

    private function number(string $prefix, EtimsSubmission $submission): string
    {
        return 'ETIMS-'.$prefix.'-'.now()->format('Ymd').'-'.str_pad((string) $submission->id, 8, '0', STR_PAD_LEFT);
    }

    private function qrPayload(EtimsSubmission $submission, string $invoiceNumber, string $receiptNumber): string
    {
        $total = number_format((float) data_get($submission->payload, 'total', 0), 2, '.', '');
        $hash = hash('sha256', implode('|', [$invoiceNumber, $receiptNumber, $total, $submission->business_id]));

        return implode('|', ['ETIMS', $invoiceNumber, $receiptNumber, $total, substr($hash, 0, 24)]);
    }

    private function audit(EtimsSubmission $submission, string $event, string $status, ?string $message = null, ?array $payload = null): void
    {
        EtimsAuditLog::create([
            'etims_submission_id' => $submission->id,
            'event' => $event,
            'status' => $status,
            'message' => $message,
            'payload' => $payload,
        ]);
    }
}
