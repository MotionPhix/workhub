<?php

namespace App\Http\Controllers\Crm;

use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $this->user($request);

        $clients = Client::forUser($user)
            ->with(['company', 'contact', 'projects'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('clients/index', [
            'clients' => $clients,
            'filters' => [
                'status' => $request->status,
            ],
        ]);
    }

    public function show(Request $request, Client $client): Response
    {
        $client->load([
            'company',
            'contact',
            'projects' => fn ($q) => $q->orderByDesc('created_at'),
        ]);

        return Inertia::render('clients/show', [
            'client' => $client,
        ]);
    }

    public function edit(Client $client): Response
    {
        return Inertia::render('clients/edit', [
            'client' => $client,
        ]);
    }

    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'string'],
            'is_retainer' => ['sometimes', 'boolean'],
            'monthly_value' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        $client->update($data);

        return back()->with('success', 'Client updated.');
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return redirect()->route('clients.index')
            ->with('success', 'Client removed.');
    }
}
