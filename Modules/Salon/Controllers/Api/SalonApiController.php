<?php

namespace Modules\Salon\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Salon\Contracts\SalonSpaServiceContract;
use Modules\Salon\Models\Appointment;
use Modules\Salon\Repositories\SalonRepository;

class SalonApiController extends Controller
{
    public function dashboard(SalonSpaServiceContract $salon)
    {
        return response()->json(['data' => $salon->dashboard()]);
    }

    public function appointments(SalonRepository $repository)
    {
        return response()->json(['data' => $repository->upcomingAppointments(50)->get()]);
    }

    public function services(Request $request, SalonRepository $repository)
    {
        return response()->json(['data' => $repository->activeServices($request->query('q'))->limit(50)->get()]);
    }

    public function clients(Request $request, SalonRepository $repository)
    {
        return response()->json(['data' => $repository->clients($request->query('q'))->limit(50)->get()]);
    }

    public function bookAppointment(Request $request, SalonSpaServiceContract $salon)
    {
        $data = $request->validate([
            'salon_client_profile_id' => ['required', \Modules\Salon\Services\SalonRecords::exists(\Modules\Salon\Models\ClientProfile::class)],
            'salon_staff_profile_id' => ['nullable', \Modules\Salon\Services\SalonRecords::exists(\Modules\Salon\Models\StaffProfile::class)],
            'salon_resource_id' => ['nullable', \Modules\Salon\Services\SalonRecords::exists(\Modules\Salon\Models\Resource::class)],
            'starts_at' => ['required', 'date'],
            'channel' => ['nullable', 'string', 'max:80'],
            'services' => ['required', 'array', 'min:1'],
            'services.*.service_id' => ['required', \Modules\Salon\Services\SalonRecords::exists(\Modules\Salon\Models\Service::class)],
            'services.*.salon_staff_profile_id' => ['nullable', \Modules\Salon\Services\SalonRecords::exists(\Modules\Salon\Models\StaffProfile::class)],
        ]);

        $profile = \Modules\Salon\Models\ClientProfile::findOrFail($data['salon_client_profile_id']);
        $data['client_id'] = $profile->client_id;

        return response()->json(['data' => $salon->bookAppointment($data)], 201);
    }

    public function completeAppointment(Appointment $appointment, SalonSpaServiceContract $salon)
    {
        return response()->json(['data' => $salon->completeAppointment($appointment)]);
    }
}
