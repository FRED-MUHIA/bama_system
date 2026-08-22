<?php

namespace Modules\Hospitality\Services;

use App\Models\Client;
use App\Models\Contact;
use Illuminate\Support\Facades\DB;
use Modules\Hospitality\Models\GuestProfile;

class HospitalityCrmService
{
    public function syncGuest(GuestProfile $guest): GuestProfile
    {
        return DB::transaction(function () use ($guest) {
            $client = $guest->client;

            if (! $client) {
                $client = Client::query()
                    ->when($guest->email, fn ($query) => $query->where('email', $guest->email))
                    ->when(! $guest->email && $guest->phone, fn ($query) => $query->where('phone', $guest->phone))
                    ->first();
            }

            $client ??= Client::create([
                'type' => 'individual',
                'name' => $guest->full_name,
                'phone' => $guest->phone,
                'email' => $guest->email,
                'address' => $guest->address,
                'notes' => 'Created from Hospitality guest profile.',
            ]);

            $contact = $guest->contact;
            if (! $contact) {
                $contact = Contact::firstOrCreate(
                    ['client_id' => $client->id, 'email' => $guest->email],
                    ['full_name' => $guest->full_name, 'phone' => $guest->phone, 'position' => 'Guest', 'is_primary' => true]
                );
            }

            $guest->forceFill(['client_id' => $client->id, 'contact_id' => $contact->id])->save();

            return $guest->refresh();
        });
    }
}
