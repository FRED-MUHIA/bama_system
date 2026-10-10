<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\PosOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Mockery;
use Modules\Hospitality\Controllers\HospitalityFrontController;
use Modules\Hospitality\Models\RestaurantOrder;
use Modules\Hospitality\Services\HospitalityRestaurantService;
use Tests\TestCase;

class HospitalityPublicOrderTest extends TestCase
{
    public function test_direct_order_queues_without_customer_details_and_redirects_to_signed_status(): void
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')->once()->withArgs(function (array $rules) {
            $this->assertArrayNotHasKey('full_name', $rules);
            $this->assertArrayNotHasKey('email', $rules);
            $this->assertArrayNotHasKey('phone', $rules);
            $this->assertArrayNotHasKey('reserved_for', $rules);

            $this->assertContains('required', $rules['waiter_id']);
            return true;
        })->andReturn(['order_type' => 'Dine In', 'waiter_id' => 7, 'items' => [['product_id' => 1, 'quantity' => 2]]]);
        $restaurant = Mockery::mock(HospitalityRestaurantService::class);
        $order = new RestaurantOrder;
        $order->id = 123;
        $restaurant->shouldReceive('createFoodReservation')->once()->withArgs(function (array $data) {
            $this->assertSame('Queued', $data['kitchen_status']);
            $this->assertSame('Open', $data['billing_status']);
            $this->assertSame(7, $data['waiter_id']);
            $this->assertArrayNotHasKey('guest_profile_id', $data);
            $this->assertSame(2, $data['items'][0]['quantity']);

            return true;
        })->andReturn($order);

        $response = (new HospitalityFrontController)->reserve($request, $restaurant);
        $this->assertTrue(URL::hasValidSignature(Request::create($response->getTargetUrl())));
        $this->assertContains('signed', app('router')->getRoutes()->getByName('public.hospitality.order')->middleware());
    }

    public function test_receipt_is_available_only_after_serving_and_does_not_claim_payment(): void
    {
        $posOrder = new PosOrder(['order_number' => 'TEST-123', 'subtotal' => 950, 'total' => 950]);
        $posOrder->setRelation('items', new Collection);
        $posOrder->setRelation('invoice', null);
        $order = new RestaurantOrder(['kitchen_status' => 'Queued', 'billing_status' => 'Open', 'order_type' => 'Dine In']);
        $order->setRelation('posOrder', $posOrder);
        $order->setRelation('restaurantTable', null);
        $order->setRelation('room', null);
        $order->setRelation('waiter', new \App\Models\User(['name' => 'Jane Server']));
        $order->setRelation('business', null);

        $queued = view('hospitality.order', compact('order'))->render();
        $this->assertStringNotContainsString('Print receipt', $queued);
        $this->assertStringContainsString('window.location.reload()', $queued);

        $order->kitchen_status = 'Served';
        $served = view('hospitality.order', compact('order'))->render();
        $this->assertStringContainsString('Print receipt', $served);
        $this->assertStringContainsString('Payment status: Open', $served);
        $this->assertStringContainsString('Serving staff: Jane Server', $served);
        $this->assertStringNotContainsString('window.location.reload()', $served);

        $order->kitchen_status = 'Cancelled';
        $cancelled = view('hospitality.order', compact('order'))->render();
        $this->assertStringNotContainsString('Print receipt', $cancelled);
        $this->assertStringNotContainsString('window.location.reload()', $cancelled);
    }

    public function test_receipt_shows_linked_partial_payments_and_balance(): void
    {
        $receipt = new \App\Models\Receipt(['receipt_number' => 'RCP-001', 'amount_paid' => 400, 'balance_remaining' => 550, 'payment_method' => 'Cash']);
        $receipt->setRelation('payment', new \App\Models\Payment(['reference' => 'PAY-123']));
        $invoice = new \App\Models\Invoice(['amount_paid' => 400]);
        $invoice->setRelation('receipts', new Collection([$receipt]));
        $posOrder = new PosOrder(['order_number' => 'POS-001', 'total' => 950, 'subtotal' => 950]);
        $posOrder->setRelation('items', new Collection);
        $posOrder->setRelation('invoice', $invoice);
        $order = new RestaurantOrder(['kitchen_status' => 'Served', 'billing_status' => 'Open']);
        $order->setRelation('posOrder', $posOrder);
        foreach (['restaurantTable', 'room', 'waiter', 'business'] as $relation) {
            $order->setRelation($relation, null);
        }
        $html = view('hospitality.order', compact('order'))->render();
        foreach (['RCP-001', 'PAY-123', '400.00', '550.00', 'Payment status: Partial', 'Print receipt'] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }
    }

    public function test_staff_can_record_payment_for_an_unserved_order_from_the_front_listing(): void
    {
        $invoice = new Invoice(['amount_paid' => 0, 'balance' => 950]);
        $invoice->id = 5;
        $invoice->setRelation('receipts', new Collection);

        $posOrder = new PosOrder(['order_number' => 'POS-005', 'total' => 950, 'subtotal' => 950]);
        $posOrder->id = 5;
        $posOrder->setRelation('invoice', $invoice);

        $order = new RestaurantOrder(['kitchen_status' => 'Queued', 'billing_status' => 'Open', 'order_type' => 'Dine In', 'total' => 950]);
        $order->id = 5;
        $order->setRelation('posOrder', $posOrder);
        $order->setRelation('waiter', null);

        $this->actingAs(new User(['name' => 'Restaurant Staff']));
        $html = view('hospitality.front', [
            'errors' => new \Illuminate\Support\ViewErrorBag,
            'menuItems' => new Collection,
            'restaurantTables' => new Collection,
            'staff' => new Collection,
            'rooms' => new Collection,
            'paymentMethods' => new Collection,
            'recentOrders' => new Collection([$order]),
        ])->render();

        $this->assertStringContainsString('Record payment', $html);
        $this->assertStringContainsString('name="return_to" value="public.hospitality.menu"', $html);
        $this->assertStringContainsString('max="950"', $html);
    }
}
