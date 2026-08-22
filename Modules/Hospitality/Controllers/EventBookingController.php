<?php

namespace Modules\Hospitality\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Hospitality\Models\EventBooking;
use Modules\Hospitality\Models\GuestProfile;
use Modules\Hospitality\Controllers\Concerns\NormalizesHospitalityInput;
use Modules\Hospitality\Services\HospitalityBillingService;
use Modules\Hospitality\Services\HospitalityNumberService;

class EventBookingController extends Controller
{
    use NormalizesHospitalityInput;

    public function index()
    {
        return view('hospitality.index', [
            'title' => 'Events',
            'section' => 'events',
            'records' => EventBooking::with('client', 'guestProfile', 'invoice')->latest()->paginate(30),
            'clients' => Client::orderBy('name')->limit(200)->get(),
            'guests' => GuestProfile::orderBy('full_name')->limit(200)->get(),
            'statuses' => ['Pending', 'Confirmed', 'In Progress', 'Completed', 'Cancelled'],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['nullable', 'required_without:guest_profile_id', 'exists:clients,id'],
            'guest_profile_id' => ['nullable', 'required_without:client_id', 'exists:hospitality_guest_profiles,id'],
            'venue_name' => ['required', 'string', 'max:160'],
            'event_type' => ['nullable', 'string', 'max:100'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'attendees' => ['nullable', 'integer', 'min:0'],
            'package_name' => ['nullable', 'string', 'max:160'],
            'status' => ['required', Rule::in(['Pending', 'Confirmed', 'In Progress', 'Completed', 'Cancelled'])],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $data = $this->zeroBlankNumbers($data, ['attendees', 'total_amount']);

        $event = EventBooking::create($data + ['booking_number' => app(HospitalityNumberService::class)->eventBooking()]);
        if (($data['total_amount'] ?? 0) > 0) {
            $invoice = app(HospitalityBillingService::class)->eventInvoice($event);
            $event->update(['invoice_id' => $invoice->id]);
        }

        return back()->with('status', 'Event booking created.');
    }
}
