<?php

namespace Modules\Hospitality\Services;

use App\Models\Invoice;
use App\Models\Client;
use App\Models\PosOrder;
use App\Models\Payment;
use App\Models\Receipt;
use App\Services\DocumentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Hospitality\Models\CheckOut;
use Modules\Hospitality\Models\EventBooking;
use Modules\Hospitality\Models\Reservation;
use Modules\Hospitality\Models\RestaurantOrder;

class HospitalityBillingService
{
    public function foodInvoice(PosOrder $order, array $items): Invoice
    {
        if ($order->invoice_id) {
            return Invoice::findOrFail($order->invoice_id);
        }

        $clientId = $order->client_id ?? Client::firstOrCreate([
            'name' => 'Walk-in restaurant customer',
            'type' => 'individual',
        ])->id;
        $restaurantOrder = RestaurantOrder::where('pos_order_id', $order->id)->latest('id')->first();
        $reference = $restaurantOrder
            ? 'Hospitality restaurant '.$restaurantOrder->order_type.' · '.$order->order_number
            : 'Hospitality restaurant '.$order->order_number;
        $invoice = $this->createInvoice($clientId, $items, $reference);
        $order->update(['client_id' => $clientId, 'invoice_id' => $invoice->id]);

        return $invoice;
    }

    public function __construct(private readonly DocumentService $documents)
    {
    }

    public function reservationInvoice(Reservation $reservation, array $extraItems = []): Invoice
    {
        if ($reservation->checkOut?->invoice_id) {
            return Invoice::findOrFail($reservation->checkOut->invoice_id);
        }

        if ($reservation->checkIn?->invoice_id) {
            return Invoice::findOrFail($reservation->checkIn->invoice_id);
        }

        $guest = $reservation->guestProfile;
        if ($guest && ! $guest->client_id) {
            $guest = app(HospitalityCrmService::class)->syncGuest($guest);
        }

        $nights = max(1, $reservation->arrival_date->diffInDays($reservation->departure_date));
        $roomRate = (float) ($reservation->room?->price_per_night ?: $reservation->roomType?->base_price ?: 0);

        $items = array_merge([[
            'title' => 'Room stay - '.$reservation->reservation_number,
            'description' => trim(($reservation->room?->room_number ? 'Room '.$reservation->room->room_number.'. ' : '').$reservation->arrival_date->toDateString().' to '.$reservation->departure_date->toDateString()),
            'quantity' => $nights,
            'unit_price' => $roomRate,
            'discount' => 0,
            'tax_rate' => 0,
        ]], $extraItems);

        return $this->createInvoice($guest?->client_id ?? $reservation->client_id, $items, 'Hospitality reservation '.$reservation->reservation_number);
    }

    public function finalBill(CheckOut $checkOut): Invoice
    {
        if ($checkOut->invoice_id) {
            return Invoice::findOrFail($checkOut->invoice_id);
        }

        $reservation = $checkOut->reservation;
        $guest = $reservation->guestProfile;
        if ($guest && ! $guest->client_id) {
            $guest = app(HospitalityCrmService::class)->syncGuest($guest);
        }

        $items = [];
        $stayInvoice = $reservation->checkIn?->invoice;
        $stayTotal = 0.0;
        if ($stayInvoice && (float) $stayInvoice->total > 0) {
            $items[] = ['title' => 'Room stay - '.$reservation->reservation_number, 'description' => 'Accommodation charges from check-in invoice '.$stayInvoice->invoice_number, 'quantity' => 1, 'unit_price' => (float) $stayInvoice->total, 'discount' => 0, 'tax_rate' => 0];
            $stayTotal = (float) $stayInvoice->total;
        }

        $separatelyInvoicedRestaurant = (float) RestaurantOrder::query()
            ->where('reservation_id', $reservation->id)
            ->where('billing_status', 'Paid')
            ->whereHas('posOrder', fn ($query) => $query->whereNotNull('invoice_id'))
            ->sum('total');

        $items = array_merge($items, array_filter([
            ['title' => 'Restaurant charges', 'description' => 'Restaurant POS and room service charges, excluding separately invoiced orders', 'quantity' => 1, 'unit_price' => max((float) $checkOut->restaurant_charges - $separatelyInvoicedRestaurant, 0), 'discount' => 0, 'tax_rate' => 0],
            ['title' => 'Event charges', 'description' => 'Event venue, catering, and equipment charges', 'quantity' => 1, 'unit_price' => max((float) $checkOut->event_charges - $this->separatelyInvoicedEventTotal($reservation), 0), 'discount' => 0, 'tax_rate' => 0],
            ['title' => 'Other services', 'description' => 'Additional hospitality services', 'quantity' => 1, 'unit_price' => (float) $checkOut->other_charges, 'discount' => 0, 'tax_rate' => 0],
        ], fn ($item) => $item['unit_price'] > 0));

        $itemTotal = array_sum(array_map(fn ($item) => (float) $item['unit_price'] * (float) $item['quantity'], $items));
        $remainingFinalAmount = max((float) $checkOut->final_amount - $stayTotal, 0);
        if ($remainingFinalAmount > $itemTotal) {
            $items[] = ['title' => 'Other services', 'description' => 'Additional checkout charges', 'quantity' => 1, 'unit_price' => $remainingFinalAmount - $itemTotal, 'discount' => 0, 'tax_rate' => 0];
        }

        if (! $items) {
            throw ValidationException::withMessages(['final_amount' => 'Enter a final bill amount or at least one checkout charge before completing checkout.']);
        }

        return $this->createInvoice($guest?->client_id ?? $reservation->client_id, $items, 'Hospitality checkout '.$reservation->reservation_number);
    }

    public function eventInvoice(EventBooking $event): Invoice
    {
        if ($event->invoice_id) {
            return Invoice::findOrFail($event->invoice_id);
        }

        $guest = $event->guestProfile;
        if ($guest && ! $guest->client_id) {
            $guest = app(HospitalityCrmService::class)->syncGuest($guest);
        }

        return $this->createInvoice($event->client_id ?? $guest?->client_id, [[
            'title' => 'Event booking - '.$event->booking_number,
            'description' => trim($event->venue_name.' '.$event->starts_at->toDateString()),
            'quantity' => 1,
            'unit_price' => (float) $event->total_amount,
            'discount' => 0,
            'tax_rate' => 0,
        ]], 'Hospitality event '.$event->booking_number);
    }

    public function collectPayment(Invoice $invoice, float $amount, string $method = 'Cash', ?string $reference = null): Receipt
    {
        return DB::transaction(function () use ($invoice, $amount, $method, $reference) {
            $invoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            if ($amount <= 0 || round($amount, 2) > round((float) $invoice->balance, 2)) {
                throw ValidationException::withMessages(['payment_amount' => 'Enter a positive payment no greater than the invoice balance.']);
            }
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'payment_date' => now()->toDateString(),
                'reference' => $reference,
                'notes' => 'Hospitality payment collection.',
            ]);

            // Payment's model hook synchronizes the invoice from its payment records.
            $invoice->refresh();
            $balance = max((float) $invoice->total - (float) $invoice->amount_paid, 0);
            $invoice->update(['balance' => $balance, 'payment_status' => $balance <= 0 ? 'paid' : 'partial']);

            return Receipt::create([
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'receipt_number' => $this->documents->number('receipt'),
                'amount_paid' => $amount,
                'balance_remaining' => $balance,
                'status' => 'paid',
                'payment_method' => $method,
                'payment_date' => now()->toDateString(),
            ]);
        });
    }

    private function separatelyInvoicedEventTotal(Reservation $reservation): float
    {
        $query = EventBooking::query()
            ->where('guest_profile_id', $reservation->guest_profile_id)
            ->whereNotNull('invoice_id')
            ->whereBetween('starts_at', [
                $reservation->arrival_date->startOfDay(),
                $reservation->departure_date->endOfDay(),
            ]);

        return (float) $query->sum('total_amount');
    }

    private function createInvoice(?int $clientId, array $items, string $notes): Invoice
    {
        if (! $clientId) {
            throw ValidationException::withMessages(['client_id' => 'Hospitality billing requires a CRM client or synced guest profile.']);
        }

        return DB::transaction(function () use ($clientId, $items, $notes) {
            $items = $this->documents->normalizeItems($items);
            $totals = $this->documents->totals($items);

            $invoice = Invoice::create([
                'client_id' => $clientId,
                'invoice_number' => $this->documents->number('invoice'),
                'industry_module' => 'hospitality',
                'industry_reference' => trim(preg_replace('/^Hospitality\s+/i', '', $notes)),
                'industry_context' => ['module' => 'hospitality', 'source' => 'hospitality_billing'],
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(7)->toDateString(),
                'payment_status' => 'unpaid',
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discountTotal'],
                'tax_total' => $totals['taxTotal'],
                'total' => $totals['total'],
                'amount_paid' => 0,
                'balance' => $totals['total'],
                'notes' => $notes,
            ]);

            foreach ($items as $item) {
                $invoice->items()->create([
                    'title' => $item['title'] ?? $item['description'],
                    'description' => $item['description'] ?? $item['title'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'line_total' => $this->documents->lineTotal($item),
                ]);
            }

            return $invoice->load('items', 'client');
        });
    }
}
