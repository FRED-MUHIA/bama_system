<?php

namespace Modules\Hospitality\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Modules\Hospitality\Models\CheckIn;
use Modules\Hospitality\Models\GuestProfile;
use Modules\Hospitality\Models\Reservation;
use Modules\Hospitality\Models\Room;
use Modules\Hospitality\Controllers\Concerns\NormalizesHospitalityInput;
use Modules\Hospitality\Services\HospitalityOperationsService;

class CheckInController extends Controller
{
    use NormalizesHospitalityInput;

    public function index()
    {
        return view('hospitality.index', [
            'title' => 'Check-In',
            'section' => 'check-ins',
            'records' => CheckIn::with('reservation.guestProfile', 'reservation.client', 'room', 'invoice')->latest()->paginate(20),
            'reservations' => Reservation::with('guestProfile', 'client')->whereIn('status', ['Pending', 'Confirmed'])->latest()->limit(100)->get(),
            'clients' => Client::orderBy('name')->limit(250)->get(),
            'rooms' => Room::with('roomType')
                ->where('status', '!=', 'Out Of Service')
                ->orderBy('floor')
                ->orderBy('sort_order')
                ->orderBy('room_number')
                ->get(),
        ]);
    }

    public function store(Request $request, HospitalityOperationsService $operations)
    {
        $data = $request->validate([
            'reservation_id' => ['nullable', 'required_without:client_id', 'exists:hospitality_reservations,id'],
            'client_id' => ['nullable', 'required_without:reservation_id', 'exists:clients,id'],
            'room_id' => ['required', 'exists:hospitality_rooms,id'],
            'arrival_date' => ['nullable', 'date'],
            'departure_date' => ['nullable', 'date', 'after:arrival_date'],
            'access_code' => ['nullable', 'string', 'max:80'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $reservation = $this->reservationForCheckIn($data, $operations);
        $checkIn = $operations->checkIn($reservation, $this->zeroBlankNumbers($data, ['deposit_amount']));

        return back()->with('status', 'Checked in with invoice '.$checkIn->invoice?->invoice_number.'.');
    }

    private function reservationForCheckIn(array $data, HospitalityOperationsService $operations): Reservation
    {
        if (! empty($data['reservation_id'])) {
            $reservation = Reservation::with('guestProfile')->findOrFail($data['reservation_id']);

            if (! empty($data['client_id'])) {
                $reservation->update(['client_id' => $data['client_id']]);
                $reservation->guestProfile?->update(['client_id' => $data['client_id']]);
            }

            return $reservation;
        }

        $client = Client::findOrFail($data['client_id']);
        $guest = GuestProfile::firstOrCreate(
            ['client_id' => $client->id],
            [
                'full_name' => $client->name,
                'phone' => $client->phone,
                'email' => $client->email,
                'address' => $client->address,
                'preferences' => [],
            ]
        );
        $room = Room::with('roomType')->findOrFail($data['room_id']);
        $arrival = $data['arrival_date'] ?? now()->toDateString();
        $departure = $data['departure_date'] ?? now()->addDay()->toDateString();

        return $operations->createReservation([
            'guest_profile_id' => $guest->id,
            'client_id' => $client->id,
            'room_id' => $room->id,
            'room_type_id' => $room->room_type_id,
            'arrival_date' => $arrival,
            'departure_date' => $departure,
            'adults' => 1,
            'children' => 0,
            'booking_source' => 'Walk In',
            'deposit_amount' => $data['deposit_amount'] ?? 0,
            'total_amount' => (float) $room->price_per_night,
            'special_requests' => $data['notes'] ?? null,
        ]);
    }
}
