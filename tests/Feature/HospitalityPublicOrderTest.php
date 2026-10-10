<?php

namespace Tests\Feature;

use App\Models\PosOrder;
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

            return true;
        })->andReturn(['order_type' => 'Dine In', 'items' => [['product_id' => 1, 'quantity' => 2]]]);
        $restaurant = Mockery::mock(HospitalityRestaurantService::class);
        $order = new RestaurantOrder;
        $order->id = 123;
        $restaurant->shouldReceive('createFoodReservation')->once()->withArgs(function (array $data) {
            $this->assertSame('Queued', $data['kitchen_status']);
            $this->assertSame('Open', $data['billing_status']);
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
        $order = new RestaurantOrder(['kitchen_status' => 'Queued', 'billing_status' => 'Open', 'order_type' => 'Dine In']);
        $order->setRelation('posOrder', $posOrder);
        $order->setRelation('restaurantTable', null);

        $queued = view('hospitality.order', compact('order'))->render();
        $this->assertStringNotContainsString('Print receipt', $queued);
        $this->assertStringContainsString('window.location.reload()', $queued);

        $order->kitchen_status = 'Served';
        $served = view('hospitality.order', compact('order'))->render();
        $this->assertStringContainsString('Print receipt', $served);
        $this->assertStringContainsString('Payment status: Open', $served);
        $this->assertStringNotContainsString('window.location.reload()', $served);

        $order->kitchen_status = 'Cancelled';
        $cancelled = view('hospitality.order', compact('order'))->render();
        $this->assertStringNotContainsString('Print receipt', $cancelled);
        $this->assertStringNotContainsString('window.location.reload()', $cancelled);
    }
}
