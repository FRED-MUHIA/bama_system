<?php

namespace Tests\Unit;

use Tests\TestCase;

class MpesaCheckoutMarkupTest extends TestCase
{
    public function test_checkout_accepts_and_normalizes_a_formatted_international_phone_number(): void
    {
        $view = file_get_contents(resource_path('views/billing/index.blade.php'));

        $this->assertStringContainsString('$mpesaPhonePattern', $view);
        $this->assertStringContainsString('maxlength="17"', $view);
        $this->assertStringContainsString("normalizePhone(input.value)", $view);
        $this->assertStringContainsString("input?.setCustomValidity(error)", $view);
        $this->assertStringContainsString('M-PESA could not authenticate this STK request.', $view);
    }
}
