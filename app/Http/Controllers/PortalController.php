<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientPortalInvitation;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use App\Support\ActiveBusiness;
use App\Support\ActiveTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class PortalController extends Controller
{
    public function activateForm(string $token)
    {
        abort_unless(Schema::hasTable('client_portal_invitations'), 404);
        $invitation = ClientPortalInvitation::where('token', $token)->where('status', 'Invited')->firstOrFail();

        return view('portal.activate', compact('invitation'));
    }

    public function activate(Request $request, string $token)
    {
        abort_unless(Schema::hasTable('client_portal_invitations'), 404);
        $invitation = ClientPortalInvitation::where('token', $token)->where('status', 'Invited')->firstOrFail();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = User::where('email', $invitation->email)->first();
        abort_if($user && $user->role !== 'client_portal', 422, 'This email is already used for a workspace login.');

        $user ??= User::create([
            'name' => $data['name'],
            'username' => $invitation->email,
            'role' => 'client_portal',
            'is_active' => true,
            'password' => Hash::make($data['password']),
        ]);

        $invitation->update(['user_id' => $user->id, 'status' => 'Activated', 'activated_at' => now()]);
        Auth::login($user);
        $request->session()->regenerate();
        ActiveTenant::clear();
        ActiveBusiness::clear();
        if (Schema::hasColumn('users', 'current_tenant_id') && $user->current_tenant_id) {
            $user->forceFill(['current_tenant_id' => null])->saveQuietly();
        }

        return redirect()->route('portal.dashboard');
    }

    public function dashboard()
    {
        abort_unless(Schema::hasTable('client_portal_invitations'), 404);
        $invitation = ClientPortalInvitation::withoutGlobalScope('business')
            ->where('user_id', Auth::id())
            ->latest()
            ->firstOrFail();
        $clientId = $invitation->client_id;
        $businessId = $invitation->business_id;
        $client = Client::withoutGlobalScope('business')
            ->where('business_id', $businessId)
            ->findOrFail($clientId);
        $invitation->setRelation('client', $client);

        return view('portal.dashboard', [
            'invitation' => $invitation,
            'projects' => Project::withoutGlobalScope('business')
                ->where('business_id', $businessId)
                ->where('client_id', $clientId)
                ->with([
                    'documents' => fn ($query) => $query->withoutGlobalScope('business')->where('business_id', $businessId),
                    'warranties' => fn ($query) => $query->withoutGlobalScope('business')->where('business_id', $businessId),
                ])
                ->latest()
                ->get(),
            'invoices' => Invoice::withoutGlobalScope('business')
                ->where('business_id', $businessId)
                ->where('client_id', $clientId)
                ->source()
                ->latest()
                ->get(),
        ]);
    }
}
