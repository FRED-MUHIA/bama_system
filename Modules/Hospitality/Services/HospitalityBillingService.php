<?php

namespace Modules\Hospitality\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Receipt;
use App\Services\DocumentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Hospitality\Models\CheckOut;
use Modules\Hospitality\Models\EventBooking;
use Modules\Hospitality\Models\Reservation;

class HospitalityBillingService
{
    public function __construct(private readonly DocumentService $documents)
    {
    }

    public function reservationInvoice(Reservation $reservation, array $extraItems = []): Invoice
    {
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
        $reservation = $checkOut->reservation;
        $guest = $reservation->guestProfile;
        if ($guest && ! $guest->client_id) {
            $guest = app(HospitalityCrmService::class)->syncGuest($guest);
        }

        $items = array_filter([
            ['title' => 'Restaurant charges', 'description' => 'Restaurant POS and room service charges', 'quantity' => 1, 'unit_price' => (float) $checkOut->restaurant_charges, 'discount' => 0, 'tax_rate' => 0],
            ['title' => 'Event charges', 'description' => 'Event venue, catering, and equipment charges', 'quantity' => 1, 'unit_price' => (float) $checkOut->event_charges, 'discount' => 0, 'tax_rate' => 0],
            ['title' => 'Other services', 'description' => 'Additional hospitality services', 'quantity' => 1, 'unit_price' => (float) $checkOut->other_charges, 'discount' => 0, 'tax_rate' => 0],
        ], fn ($item) => $item['unit_price'] > 0);

        if (! $items) {
            $items[] = ['title' => 'Final bill reconciliation', 'description' => 'Checkout final bill for '.$reservation->reservation_number, 'quantity' => 1, 'unit_price' => (float) $checkOut->final_amount, 'discount' => 0, 'tax_rate' => 0];
        }

        return $this->createInvoice($guest?->client_id ?? $reservation->client_id, $items, 'Hospitality checkout '.$reservation->reservation_number);
    }

    public function eventInvoice(EventBooking $event): Invoice
    {
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
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'payment_date' => now()->toDateString(),
                'reference' => $reference,
                'notes' => 'Hospitality payment collection.',
            ]);

            $invoice->increment('amount_paid', $amount);
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
