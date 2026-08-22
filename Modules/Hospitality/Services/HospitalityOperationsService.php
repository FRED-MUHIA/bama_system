<?php

namespace Modules\Hospitality\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Hospitality\Models\CheckIn;
use Modules\Hospitality\Models\CheckOut;
use Modules\Hospitality\Models\Reservation;
use Modules\Hospitality\Models\Room;

class HospitalityOperationsService
{
    public function createReservation(array $data): Reservation
    {
        return DB::transaction(function () use ($data) {
            $this->ensureNoConflict($data['room_id'] ?? null, $data['arrival_date'], $data['departure_date']);

            $reservation = Reservation::create($data + [
                'reservation_number' => app(HospitalityNumberService::class)->reservation(),
                'status' => 'Pending',
            ]);

            if ($reservation->room) {
                $reservation->room->update(['status' => 'Reserved']);
            }

            return $reservation->load('guestProfile', 'room', 'roomType');
        });
    }

    public function ensureNoConflict(?int $roomId, string $arrival, string $departure, ?int $ignoreReservationId = null): void
    {
        if (! $roomId) {
            return;
        }

        $conflict = Reservation::query()
            ->where('room_id', $roomId)
            ->whereNotIn('status', ['Cancelled', 'No Show', 'Checked Out'])
            ->when($ignoreReservationId, fn ($query) => $query->whereKeyNot($ignoreReservationId))
            ->whereDate('arrival_date', '<', $departure)
            ->whereDate('departure_date', '>', $arrival)
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages(['room_id' => 'This room has a conflicting reservation in the selected date range.']);
        }
    }

    public function checkIn(Reservation $reservation, array $data): CheckIn
    {
        return DB::transaction(function () use ($reservation, $data) {
            $room = Room::findOrFail($data['room_id'] ?? $reservation->room_id);

            $invoice = app(HospitalityBillingService::class)->reservationInvoice($reservation->forceFill(['room_id' => $room->id]));
            $checkIn = CheckIn::create([
                'reservation_id' => $reservation->id,
                'room_id' => $room->id,
                'guest_profile_id' => $reservation->guest_profile_id,
                'invoice_id' => $invoice->id,
                'access_code' => $data['access_code'] ?? strtoupper(str()->random(8)),
                'deposit_amount' => $data['deposit_amount'] ?? $reservation->deposit_amount,
                'checked_in_at' => now(),
                'created_by' => auth()->id(),
                'notes' => $data['notes'] ?? null,
            ]);

            $reservation->update(['room_id' => $room->id, 'status' => 'Checked In', 'checked_in_at' => now()]);
            $room->update(['status' => 'Occupied']);

            return $checkIn->load('reservation', 'room', 'invoice');
        });
    }

    public function checkOut(Reservation $reservation, array $data): CheckOut
    {
        return DB::transaction(function () use ($reservation, $data) {
            $checkOut = CheckOut::create([
                'reservation_id' => $reservation->id,
                'room_id' => $reservation->room_id,
                'guest_profile_id' => $reservation->guest_profile_id,
                'restaurant_charges' => $data['restaurant_charges'] ?? 0,
                'event_charges' => $data['event_charges'] ?? 0,
                'other_charges' => $data['other_charges'] ?? 0,
                'final_amount' => $data['final_amount'] ?? 0,
                'checked_out_at' => now(),
                'created_by' => auth()->id(),
                'notes' => $data['notes'] ?? null,
            ]);

            $invoice = app(HospitalityBillingService::class)->finalBill($checkOut->load('reservation.guestProfile', 'reservation.room', 'reservation.roomType'));
            $receipt = ! empty($data['payment_amount'])
                ? app(HospitalityBillingService::class)->collectPayment($invoice, (float) $data['payment_amount'], $data['payment_method'] ?? 'Cash', $data['payment_reference'] ?? null)
                : null;

            $checkOut->update(['invoice_id' => $invoice->id, 'receipt_id' => $receipt?->id]);
            $reservation->update(['status' => 'Checked Out', 'checked_out_at' => now()]);
            $reservation->room?->update(['status' => 'Cleaning']);

            return $checkOut->refresh()->load('reservation', 'invoice', 'receipt');
        });
    }
}
