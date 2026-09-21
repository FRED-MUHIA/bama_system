<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\PosOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Hospitality\Services\HospitalityBillingService;
use Tests\TestCase;

class HospitalityBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_food_invoice_and_partial_payment_share_the_correct_balance(): void
    {
        $business = Business::where('slug', 'bama')->firstOrFail();
        $this->actingAs(User::factory()->create(['role' => 'admin', 'is_active' => true]))
            ->withSession(['active_business_id' => $business->id]);

        $order = PosOrder::create([
            'order_number' => 'POS-HOSPITALITY-TEST', 'tracking_key' => 'HOSPITALITYTEST',
            'order_date' => now(), 'status' => 'pending', 'subtotal' => 1000,
            'total' => 1000, 'amount_paid' => 0,
        ]);
        $billing = app(HospitalityBillingService::class);
        $invoice = $billing->foodInvoice($order, [[
            'title' => 'Lunch', 'description' => 'Lunch', 'quantity' => 2,
            'unit_price' => 500, 'discount' => 0, 'tax_rate' => 0,
        ]]);

        $this->assertEquals($invoice->id, $order->fresh()->invoice_id);
        $this->assertEquals(1000, $invoice->total);
        $this->assertEquals($business->id, $invoice->business_id);
        $receipt = $billing->collectPayment($invoice, 400);
        $this->assertEquals(400, $invoice->fresh()->amount_paid);
        $this->assertEquals(600, $invoice->fresh()->balance);
        $this->assertEquals(600, $receipt->balance_remaining);
        $this->assertEquals($invoice->id, $receipt->invoice_id);

        $billing->collectPayment($invoice, 600);
        $this->assertEquals(1000, $invoice->fresh()->amount_paid);
        $this->assertEquals(0, $invoice->fresh()->balance);
        $this->assertCount(2, $invoice->fresh()->receipts);
    }
}
