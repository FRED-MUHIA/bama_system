<?php

namespace Modules\Retail\Services;

use App\Models\Client;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Retail\Models\CameraScanEvent;
use Modules\Retail\Models\ScanDevice;
use Modules\Retail\Models\ScanEvent;
use Modules\Retail\Models\SelfCheckoutTransaction;

class SmartProductScanningService
{
    public function __construct(
        private QrDecoderService $decoder,
        private ProductIdentificationService $identifier,
        private ProductComplianceService $compliance,
        private RetailPromotionService $promotions,
        private RetailInventoryService $inventory,
        private BatchTrackingService $batches,
        private FraudDetectionService $fraud,
        private ScanAuditService $audit,
    ) {
    }

    public function scan(array $input, bool $updateInventory = false): array
    {
        $updateInventory = $updateInventory || (bool) ($input['update_inventory'] ?? false);

        return DB::transaction(function () use ($input, $updateInventory) {
            $decoded = $this->decoder->decode($input);
            $product = $decoded['identifier_value'] ? $this->identifier->lookup($decoded['identifier_type'], $decoded['identifier_value']) : null;
            $batch = $product ? $this->identifier->batchFor($product, $decoded['payload']) : null;
            $quantity = (float) ($input['quantity'] ?? 1);
            $branchId = $input['branch_id'] ?? null;
            $client = ! empty($input['client_id']) ? Client::find($input['client_id']) : null;
            $lineTotal = $product ? (float) $product->price * $quantity : 0;
            $discount = $product ? $this->promotions->discountFor($product, $lineTotal, $client, $branchId) : 0;
            $device = $this->device($input);
            $before = $product ? (float) $product->stock_quantity : null;

            $event = ScanEvent::create([
                'scan_device_id' => $device?->id,
                'product_id' => $product?->id,
                'pos_order_id' => $input['pos_order_id'] ?? null,
                'branch_id' => $branchId ?? $device?->branch_id,
                'retail_warehouse_id' => $input['retail_warehouse_id'] ?? $device?->retail_warehouse_id,
                'cashier_id' => $input['cashier_id'] ?? auth()->id(),
                'input_type' => $input['input_type'] ?? 'Scanner Device Input',
                'symbology' => $decoded['symbology'],
                'raw_value' => $decoded['raw_value'],
                'identifier_type' => $decoded['identifier_type'],
                'identifier_value' => $decoded['identifier_value'],
                'result' => $product ? 'Success' : 'Failure',
                'message' => $product ? 'Product identified.' : 'Product was not found.',
                'quantity' => $quantity,
                'before_quantity' => $before,
                'original_price' => $lineTotal,
                'discount' => $discount,
                'final_price' => max($lineTotal - $discount, 0),
                'decoded_payload' => $decoded['payload'],
                'promotion_payload' => $product ? $this->promotionPayload($product, $lineTotal, $discount) : [],
                'scanned_at' => now(),
            ]);

            $verification = $this->compliance->verify($product, $batch, $decoded['payload'], $event, $input + $decoded);
            $fraud = $this->fraud->assess($event, $verification);
            $blocked = $verification->verification_result !== 'Verified';

            if ($blocked) {
                $event->update([
                    'result' => 'Failure',
                    'message' => $verification->message,
                    'compliance_payload' => $verification->checks + $fraud,
                ]);
                $this->audit->log($event->refresh(), $this->auditEventFor($verification), 'Failure', $verification->checks + $fraud);

                return $this->response($event->refresh(), $product, $verification, $fraud);
            }

            if ($updateInventory && $product) {
                $this->inventory->adjust($product, -abs($quantity), 'available_stock', [
                    'branch_id' => $branchId,
                    'retail_warehouse_id' => $input['retail_warehouse_id'] ?? null,
                    'reference' => 'Smart scan sale',
                    'notes' => 'Inventory deducted by Smart Product Scanning.',
                ], $event);
                if ($batch) {
                    $this->batches->sell($batch, $quantity, $event);
                }
                $event->update([
                    'sold_quantity' => $quantity,
                    'remaining_quantity' => (float) $product->fresh()->stock_quantity,
                ]);
            }

            $this->audit->log($event->refresh(), 'product-scanned', 'Success', $verification->checks + $fraud);

            return $this->response($event->refresh(), $product, $verification, $fraud);
        });
    }

    public function verify(array $input): array
    {
        return $this->scan($input, false);
    }

    public function camera(array $input): array
    {
        $decoded = $this->decoder->decode($input);
        $input = array_filter($input + [
            'raw_value' => $decoded['raw_value'],
            'decoded_text' => $decoded['payload']['_decoded_text'] ?? null,
            'image_path' => $decoded['payload']['_image_path'] ?? null,
            'symbology' => $decoded['symbology'],
        ], fn ($value) => $value !== null && $value !== '');

        $camera = CameraScanEvent::create([
            'scan_device_id' => $input['scan_device_id'] ?? null,
            'camera_type' => $input['camera_type'] ?? 'POS Camera',
            'image_path' => $input['image_path'] ?? null,
            'detection_result' => $decoded['identifier_value'] ? 'Detected' : 'No Code Detected',
            'detected_codes' => $decoded['payload']['_detected_codes'] ?? [$decoded['identifier_value'] ?: $decoded['raw_value']],
            'confidence' => $input['confidence'] ?? 95,
        ]);

        $result = $this->scan($input + ['input_type' => 'Camera Image Capture'], false);
        $camera->update([
            'scan_event_id' => $result['scan_event']['id'],
            'detected_products' => $result['product'] ? [$result['product']] : [],
            'detection_result' => $result['success'] ? 'Identified' : 'Failed',
            'message' => $result['message'],
        ]);

        return $result + ['camera_event' => $camera->fresh()];
    }

    public function selfCheckout(array $input): SelfCheckoutTransaction
    {
        return DB::transaction(function () use ($input) {
            $cart = collect($input['scans'] ?? [])
                ->map(fn (array $scan) => $this->scan($scan + $input, false))
                ->values();

            if ($cart->contains(fn ($result) => ! $result['success'])) {
                throw ValidationException::withMessages(['self_checkout' => 'One or more scanned products require staff review.']);
            }

            $subtotal = $cart->sum(fn ($result) => (float) data_get($result, 'pricing.original_price', 0));
            $discount = $cart->sum(fn ($result) => (float) data_get($result, 'pricing.discount', 0));
            $total = $cart->sum(fn ($result) => (float) data_get($result, 'pricing.final_price', 0));

            return SelfCheckoutTransaction::create([
                'scan_device_id' => $input['scan_device_id'] ?? null,
                'client_id' => $input['client_id'] ?? null,
                'branch_id' => $input['branch_id'] ?? null,
                'checkout_number' => 'SCO-'.now()->format('YmdHis').'-'.random_int(100, 999),
                'status' => 'Ready For Payment',
                'subtotal' => $subtotal,
                'discount_total' => $discount,
                'tax_total' => 0,
                'total' => $total,
                'payment_status' => $input['payment_status'] ?? 'Pending',
                'payment_method' => $input['payment_method'] ?? 'Mobile Payment',
                'receipt_channel' => $input['receipt_channel'] ?? 'Digital Receipt',
                'cart_payload' => $cart->all(),
            ]);
        });
    }

    private function device(array $input): ?ScanDevice
    {
        if (! empty($input['scan_device_id'])) {
            return ScanDevice::find($input['scan_device_id']);
        }

        if (empty($input['device_code'])) {
            return null;
        }

        return ScanDevice::firstOrCreate(
            ['device_code' => $input['device_code']],
            [
                'name' => $input['device_name'] ?? $input['device_code'],
                'device_type' => $input['device_type'] ?? 'POS Scanner',
                'branch_id' => $input['branch_id'] ?? null,
                'retail_warehouse_id' => $input['retail_warehouse_id'] ?? null,
                'register_number' => $input['register_number'] ?? null,
                'capabilities' => $input['capabilities'] ?? ['1D Barcode', '2D Barcode', 'QR'],
                'last_seen_at' => now(),
            ]
        );
    }

    private function promotionPayload(Product $product, float $lineTotal, float $discount): array
    {
        return [
            'original_price' => $lineTotal,
            'discount' => $discount,
            'final_price' => max($lineTotal - $discount, 0),
            'discount_eligible' => $discount > 0,
            'loyalty_eligible' => true,
        ];
    }

    private function auditEventFor($verification): string
    {
        $checks = $verification->checks ?: [];

        return match (false) {
            $checks['not_expired'] ?? true => 'expired-product',
            $checks['not_recalled'] ?? true => 'recalled-product',
            $checks['not_quarantined'] ?? true => 'quarantined-product',
            $checks['age_verified'] ?? true => 'age-verification-block',
            default => $verification->fraud_suspected ? 'fraud-detection' : 'scan-failure',
        };
    }

    private function response(ScanEvent $event, ?Product $product, $verification, array $fraud): array
    {
        return [
            'success' => $event->result === 'Success',
            'message' => $event->message,
            'scan_event' => $event->toArray(),
            'product' => $product ? $this->identifier->response($product->fresh(['category', 'retailProfile', 'retailInventoryBalances']), $event->promotion_payload ?: []) : null,
            'pricing' => [
                'original_price' => (float) $event->original_price,
                'discount' => (float) $event->discount,
                'final_price' => (float) $event->final_price,
            ],
            'verification' => $verification->toArray(),
            'fraud' => $fraud,
            'cart_item' => $product && $event->result === 'Success' ? [
                'product_id' => $product->id,
                'title' => $product->name,
                'description' => $product->description ?: $product->name,
                'quantity' => (float) $event->quantity,
                'unit_price' => (float) $product->price,
                'discount' => (float) $event->discount,
                'tax_rate' => 0,
            ] : null,
        ];
    }
}
