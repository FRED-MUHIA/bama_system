<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClientMergeService
{
    public function merge(Client $source, Client $target): Client
    {
        if ($source->is($target)) {
            throw ValidationException::withMessages(['target_client_id' => 'Choose a different target client.']);
        }

        if ((int) $source->business_id !== (int) $target->business_id) {
            throw ValidationException::withMessages(['target_client_id' => 'Clients must belong to the same business.']);
        }

        DB::transaction(function () use ($source, $target) {
            $source->contacts()->update(['client_id' => $target->id]);
            $source->sites()->update(['client_id' => $target->id]);
            $source->projects()->update(['client_id' => $target->id]);
            $source->quotations()->update(['client_id' => $target->id]);
            $source->invoices()->update(['client_id' => $target->id]);
            $source->posOrders()->update(['client_id' => $target->id]);
            $source->delete();
        });

        return $target->refresh();
    }
}
