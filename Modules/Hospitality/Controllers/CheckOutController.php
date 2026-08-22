<?php

namespace Modules\Hospitality\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Hospitality\Controllers\Concerns\NormalizesHospitalityInput;
use Modules\Hospitality\Models\CheckOut;
use Modules\Hospitality\Models\Reservation;
use Modules\Hospitality\Services\HospitalityOperationsService;

class CheckOutController extends Controller
{
    use NormalizesHospitalityInput;

    public function index()
    {
        return view('hospitality.index', [
            'title' => 'Check-Out',
            'section' => 'check-outs',
            'records' => CheckOut::with('reservation.guestProfile', 'invoice', 'receipt')->latest()->paginate(20),
            'reservations' => Reservation::with('guestProfile', 'room')->where('status', 'Checked In')->latest()->limit(100)->get(),
        ]);
    }

    public function store(Request $request, HospitalityOperationsService $operations)
    {
        $data = $request->validate([
            'reservation_id' => ['required', 'exists:hospitality_reservations,id'],
            'restaurant_charges' => ['nullable', 'numeric', 'min:0'],
            'event_charges' => ['nullable', 'numeric', 'min:0'],
            'other_charges' => ['nullable', 'numeric', 'min:0'],
            'final_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:80'],
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string'],
        ]);

        $checkOut = $operations->checkOut(Reservation::findOrFail($data['reservation_id']), $this->zeroBlankNumbers($data, [
            'restaurant_charges',
            'event_charges',
            'other_charges',
            'final_amount',
            'payment_amount',
        ]));

        return back()->with('status', 'Checked out with final invoice '.$checkOut->invoice?->invoice_number.'.');
    }
}
