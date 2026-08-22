<?php

namespace Modules\Retail\Services;

use App\Models\Product;
use Illuminate\Validation\ValidationException;
use Modules\Retail\Models\ProductBatch;
use Modules\Retail\Models\ProductVerification;
use Modules\Retail\Models\ScanEvent;

class ProductComplianceService
{
    public function verify(?Product $product, ?ProductBatch $batch, array $payload, ?ScanEvent $event = null, array $context = []): ProductVerification
    {
        $checks = [
            'product_exists' => (bool) $product,
            'product_active' => (bool) ($product?->is_active),
            'batch_valid' => $this->batchValid($batch, $payload),
            'not_recalled' => ! $batch || $batch->recall_status !== 'Recalled',
            'not_expired' => ! $this->expired($batch, $payload),
            'not_quarantined' => ! $batch || ! in_array($batch->status, ['Quarantined', 'Disabled'], true),
            'compliant' => ! $batch || $batch->compliance_status === 'Compliant',
            'age_verified' => $this->ageVerified($product, $payload, $context),
        ];

        $blocked = collect($checks)->contains(false);
        $fraud = ! $checks['product_exists'] || ($batch && $batch->product_id !== $product?->id);
        $message = $this->message($checks, $batch);

        return ProductVerification::create([
            'scan_event_id' => $event?->id,
            'product_id' => $product?->id,
            'product_batch_id' => $batch?->id,
            'identifier_type' => $context['identifier_type'] ?? null,
            'identifier_value' => $context['identifier_value'] ?? null,
            'verification_result' => $blocked ? 'Blocked' : 'Verified',
            'risk_level' => $fraud ? 'High' : ($blocked ? 'Medium' : 'Low'),
            'product_exists' => $checks['product_exists'],
            'product_active' => $checks['product_active'],
            'batch_valid' => $checks['batch_valid'],
            'not_recalled' => $checks['not_recalled'],
            'fraud_suspected' => $fraud,
            'checks' => $checks,
            'message' => $message,
        ]);
    }

    public function assertSellable(ProductVerification $verification): void
    {
        if ($verification->verification_result === 'Verified') {
            return;
        }

        throw ValidationException::withMessages(['scan' => $verification->message ?: 'Product scan failed compliance verification.']);
    }

    private function batchValid(?ProductBatch $batch, array $payload): bool
    {
        return ! isset($payload['batch_number']) || (bool) $batch;
    }

    private function expired(?ProductBatch $batch, array $payload): bool
    {
        $expiry = $payload['expiry_date'] ?? $payload['expires_at'] ?? $batch?->expiry_date;

        return $expiry ? now()->startOfDay()->gt(\Illuminate\Support\Carbon::parse($expiry)->startOfDay()) : false;
    }

    private function ageVerified(?Product $product, array $payload, array $context): bool
    {
        $restricted = (bool) data_get($product?->retailProfile?->attributes, 'age_restricted', false);
        if (! $restricted) {
            return true;
        }

        if (! empty($context['manager_override'])) {
            return true;
        }

        $dob = $context['date_of_birth'] ?? $payload['date_of_birth'] ?? null;
        if (! $dob) {
            return false;
        }

        return \Illuminate\Support\Carbon::parse($dob)->age >= (int) ($context['minimum_age'] ?? 18);
    }

    private function message(array $checks, ?ProductBatch $batch): string
    {
        if (! $checks['product_exists']) {
            return 'Product was not found in the product catalog.';
        }
        if (! $checks['product_active']) {
            return 'Product is disabled.';
        }
        if (! $checks['batch_valid']) {
            return 'Product batch is invalid.';
        }
        if (! $checks['not_recalled']) {
            return 'Product batch has been recalled: '.($batch?->recall_reason ?: 'Recall active');
        }
        if (! $checks['not_expired']) {
            return 'Product is expired.';
        }
        if (! $checks['not_quarantined']) {
            return 'Product batch is quarantined or disabled.';
        }
        if (! $checks['compliant']) {
            return 'Product compliance status blocks sale.';
        }
        if (! $checks['age_verified']) {
            return 'Age verification is required before sale.';
        }

        return 'Product verified.';
    }
}
