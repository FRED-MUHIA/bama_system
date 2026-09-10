<?php

namespace Tests\Unit;

use Tests\TestCase;

class MpesaCheckoutMarkupTest extends TestCase
{
    public function test_checkout_accepts_and_normalizes_a_formatted_international_phone_number(): void
    {
        $view = file_get_contents(resource_path('views/billing/index.blade.php'));

        $this->assertStringContainsString('pattern="[+0-9 ()-]{9,20}"', $view);
        $this->assertStringContainsString('maxlength="20"', $view);
        $this->assertStringContainsString("input?.addEventListener('input', normalize);\n    normalize();", $view);
    }
}
