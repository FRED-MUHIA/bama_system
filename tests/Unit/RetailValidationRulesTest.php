<?php

namespace Tests\Unit;

use Modules\Retail\Models\RetailDelivery;
use Modules\Retail\Models\RetailOrder;
use Modules\Retail\Models\RetailPromotion;
use Modules\Retail\Services\QrDecoderService;
use PHPUnit\Framework\TestCase;

class RetailValidationRulesTest extends TestCase
{
    public function test_retail_validation_contracts_include_enterprise_surfaces(): void
    {
        $this->assertContains('Buy One Get One', RetailPromotion::TYPES);
        $this->assertContains('Delivered', RetailOrder::STATUSES);
        $this->assertContains('In Transit', RetailDelivery::STATUSES);
    }

    public function test_qr_decoder_extracts_structured_product_identifier(): void
    {
        $decoded = (new QrDecoderService())->decode([
            'raw_value' => 'sku=SKU-1|batch_number=B-1|expiry_date=2026-12-31',
        ]);

        $this->assertSame('sku', $decoded['identifier_type']);
        $this->assertSame('SKU-1', $decoded['identifier_value']);
        $this->assertSame('B-1', $decoded['payload']['batch_number']);
    }
}
