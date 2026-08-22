<?php

namespace Modules\Hospitality\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Hospitality\Models\GuestProfile;
use Modules\Hospitality\Models\Reservation;
use Modules\Hospitality\Models\Room;
use Modules\Hospitality\Models\RoomType;
use Modules\Hospitality\Controllers\Concerns\NormalizesHospitalityInput;
use Modules\Hospitality\Services\HospitalityOperationsService;

class ReservationController extends Controller
{
    use NormalizesHospitalityInput;

    public function index()
    {
        return view('hospitality.index', [
            'title' => 'Reservations',
            'section' => 'reservations',
            'records' => Reservation::with('guestProfile', 'room', 'roomType')->latest()->paginate(20),
            'rooms' => Room::orderBy('room_number')->get(),
            'roomTypes' => RoomType::where('is_active', true)->orderBy('name')->get(),
            'guests' => GuestProfile::orderBy('full_name')->limit(200)->get(),
            'statuses' => Reservation::STATUSES,
            'sources' => Reservation::BOOKING_SOURCES,
        ]);
    }

    public function store(Request $request, HospitalityOperationsService $operations)
    {
        $data = $request->validate([
            'guest_profile_id' => ['required', 'exists:hospitality_guest_profiles,id'],
            'room_id' => ['nullable', 'exists:hospitality_rooms,id'],
            'room_type_id' => ['nullable', 'exists:hospitality_room_types,id'],
            'arrival_date' => ['required', 'date'],
            'departure_date' => ['required', 'date', 'after:arrival_date'],
            'adults' => ['required', 'integer', 'min:1'],
            'children' => ['nullable', 'integer', 'min:0'],
            'special_requests' => ['nullable', 'string'],
            'booking_source' => ['required', Rule::in(Reservation::BOOKING_SOURCES)],
            'status' => ['nullable', Rule::in(Reservation::STATUSES)],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $reservation = $operations->createReservation($this->zeroBlankNumbers($data, ['children', 'deposit_amount', 'total_amount']));

        return back()->with('status', 'Reservation '.$reservation->reservation_number.' created.');
    }

    public function update(Request $request, Reservation $reservation, HospitalityOperationsService $operations)
    {
        $data = $request->validate([
            'room_id' => ['nullable', 'exists:hospitality_rooms,id'],
            'arrival_date' => ['required', 'date'],
            'departure_date' => ['required', 'date', 'after:arrival_date'],
            'status' => ['required', Rule::in(Reservation::STATUSES)],
            'special_requests' => ['nullable', 'string'],
        ]);

        $operations->ensureNoConflict($data['room_id'] ?? $reservation->room_id, $data['arrival_date'], $data['departure_date'], $reservation->id);
        $reservation->update($data);

        return back()->with('status', 'Reservation updated.');
    }
}
