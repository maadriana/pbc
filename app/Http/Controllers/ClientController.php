<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Services\ClientService;
use Illuminate\Http\Request;

class ClientController extends BaseController
{
    protected $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    public function index(Request $request)
    {
        try {
            $this->authorize('view_client');

            $clients = $this->clientService->getFilteredClients($request->all());
            return $this->paginated($clients, 'Clients retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve clients', $e->getMessage(), 500);
        }
    }

    public function store(CreateClientRequest $request)
    {
        try {
            $this->authorize('create_client');

            $client = $this->clientService->createClient($request->validated());
            return $this->success($client, 'Client created successfully', 201);
        } catch (\Exception $e) {
            return $this->error('Failed to create client', $e->getMessage(), 500);
        }
    }

    public function show(Client $client)
    {
        try {
            $this->authorize('view_client');

            $client->load(['projects.pbcRequests']);
            return $this->success($client, 'Client retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve client', $e->getMessage(), 500);
        }
    }

    public function update(UpdateClientRequest $request, Client $client)
    {
        try {
            $this->authorize('edit_client');

            $updatedClient = $this->clientService->updateClient($client, $request->validated());
            return $this->success($updatedClient, 'Client updated successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to update client', $e->getMessage(), 500);
        }
    }

    public function destroy(Client $client)
    {
        try {
            $this->authorize('delete_client');

            $this->clientService->deleteClient($client);
            return $this->success(null, 'Client deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete client', $e->getMessage(), 500);
        }
    }

    public function projects(Client $client)
    {
        try {
            $this->authorize('view_project');

            $projects = $this->clientService->getClientProjects($client);
            return $this->success($projects, 'Client projects retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve client projects', $e->getMessage(), 500);
        }
    }
}
