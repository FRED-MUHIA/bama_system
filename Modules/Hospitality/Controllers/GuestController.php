<?php

namespace Modules\Hospitality\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Hospitality\Models\GuestProfile;
use Modules\Hospitality\Models\LoyaltyMember;
use Modules\Hospitality\Services\HospitalityCrmService;
use Modules\Hospitality\Services\HospitalityNumberService;

class GuestController extends Controller
{
    public function index()
    {
        return view('hospitality.index', [
            'title' => 'Guests',
            'section' => 'guests',
            'records' => GuestProfile::with('client', 'loyaltyMember')->latest()->paginate(30),
            'loyaltyLevels' => LoyaltyMember::LEVELS,
        ]);
    }

    public function store(Request $request, HospitalityCrmService $crm)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:180'],
            'nationality' => ['nullable', 'string', 'max:80'],
            'passport_number' => ['nullable', 'string', 'max:80'],
            'id_number' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string'],
            'preferences' => ['nullable', 'string'],
            'vip_status' => ['nullable', 'boolean'],
            'blacklist_flag' => ['nullable', 'boolean'],
            'loyalty_level' => ['nullable', Rule::in(LoyaltyMember::LEVELS)],
        ]);

        $guest = GuestProfile::create(array_merge($data, [
            'preferences' => $this->csv($data['preferences'] ?? null),
            'vip_status' => $request->boolean('vip_status'),
            'blacklist_flag' => $request->boolean('blacklist_flag'),
        ]));

        $crm->syncGuest($guest);

        return back()->with('status', 'Guest profile created and synced to CRM.');
    }

    public function enrollLoyalty(GuestProfile $guest)
    {
        $member = LoyaltyMember::firstOrCreate(
            ['guest_profile_id' => $guest->id],
            ['membership_number' => app(HospitalityNumberService::class)->membership(), 'level' => $guest->loyalty_level ?: 'Bronze', 'joined_at' => now()]
        );

        $guest->update(['loyalty_level' => $member->level]);

        return back()->with('status', 'Guest enrolled in loyalty program.');
    }

    private function csv(?string $value): array
    {
        return collect(explode(',', (string) $value))->map(fn ($item) => trim($item))->filter()->values()->all();
    }
}
