<?php

namespace Modules\Hospitality\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Modules\Hospitality\Models\CheckIn;
use Modules\Hospitality\Models\CheckOut;
use Modules\Hospitality\Models\Reservation;
use Modules\Hospitality\Models\RestaurantOrder;
use Modules\Hospitality\Models\Room;

class FrontDeskController extends Controller
{
    public function index()
    {
        $arrivals = Reservation::with('guestProfile', 'client', 'room', 'roomType')
            ->whereIn('status', ['Pending', 'Confirmed'])
            ->orderBy('arrival_date')
            ->limit(20)
            ->get();

        $inHouse = Reservation::with('guestProfile', 'client', 'room', 'roomType')
            ->where('status', 'Checked In')
            ->orderBy('departure_date')
            ->limit(30)
            ->get();

        $departures = Reservation::with('guestProfile', 'client', 'room', 'roomType')
            ->where('status', 'Checked In')
            ->whereDate('departure_date', '<=', today())
            ->orderBy('departure_date')
            ->limit(20)
            ->get();

        return view('hospitality.front-desk', [
            'arrivals' => $arrivals,
            'inHouse' => $inHouse,
            'departures' => $departures,
            'clients' => Client::orderBy('name')->limit(250)->get(),
            'rooms' => Room::with('roomType')->where('status', '!=', 'Out Of Service')->orderBy('floor')->orderBy('room_number')->get(),
            'recentCheckIns' => CheckIn::with('reservation.guestProfile', 'room', 'invoice')->latest()->limit(8)->get(),
            'recentCheckOuts' => CheckOut::with('reservation.guestProfile', 'invoice', 'receipt')->latest()->limit(8)->get(),
            'roomCharges' => RestaurantOrder::with('guestProfile', 'reservation.guestProfile', 'posOrder')
                ->where('billing_status', 'Room Charge')
                ->latest()
                ->limit(8)
                ->get(),
            'metrics' => [
                'Arrivals' => $arrivals->count(),
                'In House' => $inHouse->count(),
                'Due Out' => $departures->count(),
                'Available Rooms' => Room::where('status', 'Available')->count(),
            ],
        ]);
    }
}
