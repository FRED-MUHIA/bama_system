<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use App\Services\ClientMergeService;
use App\Support\ActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function __construct(private ClientMergeService $clientMerge) {}

    public function index()
    {
        return view('clients.index', ['clients' => Client::latest()->paginate(12)]);
    }

    public function create() { return view('clients.form', ['client' => new Client()]); }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $primaryContact = $data['primary_contact'] ?? [];
        unset($data['primary_contact']);

        $client = Client::create($data);
        $this->syncPrimaryContact($client, $primaryContact, $data['type'] ?? null);

        return redirect()->route('clients.index')->with('status', 'Client created.');
    }

    public function show(Client $client)
    {
        $relationships = ['quotations', 'invoices' => fn ($query) => $query->source()->with('receipts')];
        if (Client::supportsCompanyStructure()) {
            $relationships[] = 'contacts';
            $relationships[] = 'sites';
            $relationships[] = 'projects.site';
            $relationships[] = 'projects.contact';
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('letters')) {
            $relationships[] = 'letters.project';
            $relationships[] = 'letters.invoice';
            $relationships[] = 'sites.letters';
        }

        $client->load($relationships);
        return view('clients.show', ['client' => $client, 'clients' => Client::whereKeyNot($client->id)->orderBy('name')->get()]);
    }

    public function edit(Client $client)
    {
        if (Client::supportsCompanyStructure()) {
            $client->load('primaryContact');
        }

        return view('clients.form', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $data = $this->validated($request);
        $primaryContact = $data['primary_contact'] ?? [];
        unset($data['primary_contact']);

        $client->update($data);
        $this->syncPrimaryContact($client, $primaryContact, $data['type'] ?? $client->type);

        return redirect()->route('clients.show', $client)->with('status', 'Client updated.');
    }

    public function destroy(Client $client)
    {
        $client->delete();
        return redirect()->route('clients.index')->with('status', 'Client deleted.');
    }

    public function storeContact(Request $request, Client $client)
    {
        abort_unless(Client::supportsCompanyStructure(), 404);

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        if (! empty($data['is_primary'])) {
            $client->contacts()->update(['is_primary' => false]);
        }

        $client->contacts()->create($data + ['is_primary' => (bool) ($data['is_primary'] ?? false)]);
        return back()->with('status', 'Contact added.');
    }

    public function storeSite(Request $request, Client $client)
    {
        abort_unless(Client::supportsCompanyStructure(), 404);

        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $client->sites()->create($data);
        return back()->with('status', 'Site added.');
    }

    public function storeProject(Request $request, Client $client)
    {
        abort_unless(Client::supportsCompanyStructure(), 404);

        $data = $request->validate([
            'project_name' => ['required', 'string', 'max:255'],
            'site_id' => ['nullable', Rule::exists('sites', 'id')->where('client_id', $client->id)->where('business_id', ActiveBusiness::id())],
            'contact_id' => ['nullable', Rule::exists('contacts', 'id')->where('client_id', $client->id)->where('business_id', ActiveBusiness::id())],
            'status' => ['required', Rule::in(Project::STATUSES)],
            'scope' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $project = $client->projects()->create($data);
        return redirect()->route('projects.show', $project)->with('status', 'Project added.');
    }

    public function merge(Request $request, Client $client)
    {
        abort_unless(Client::supportsCompanyStructure(), 404);

        $data = $request->validate([
            'target_client_id' => ['required', Rule::exists('clients', 'id')->where('business_id', ActiveBusiness::id())],
        ]);

        $target = Client::findOrFail($data['target_client_id']);
        $this->clientMerge->merge($client, $target);

        return redirect()->route('clients.show', $target)->with('status', 'Clients merged.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'type' => ['nullable', 'in:company,individual'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'billing_address' => ['nullable', 'string'],
            'kra_pin' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'primary_contact.full_name' => ['nullable', 'required_with:primary_contact.email,primary_contact.phone,primary_contact.position', 'string', 'max:255'],
            'primary_contact.email' => ['nullable', 'email', 'max:255'],
            'primary_contact.phone' => ['nullable', 'string', 'max:100'],
            'primary_contact.position' => ['nullable', 'string', 'max:255'],
        ]);

        if (! Client::supportsCompanyStructure()) {
            unset($data['type'], $data['billing_address'], $data['kra_pin'], $data['primary_contact']);
        }

        return $data;
    }

    private function syncPrimaryContact(Client $client, array $contact, ?string $clientType): void
    {
        if (! Client::supportsCompanyStructure() || $clientType !== 'company' || blank($contact['full_name'] ?? null)) {
            return;
        }

        $primaryContact = $client->primaryContact()->first();
        $client->contacts()->where('is_primary', true)->update(['is_primary' => false]);

        $payload = [
            'full_name' => $contact['full_name'],
            'email' => $contact['email'] ?? null,
            'phone' => $contact['phone'] ?? null,
            'position' => $contact['position'] ?? null,
            'is_primary' => true,
        ];

        $primaryContact
            ? $primaryContact->update($payload)
            : $client->contacts()->create($payload);
    }
}
