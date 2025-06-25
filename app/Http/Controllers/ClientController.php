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
            // Check permission
            if (!auth()->user()->hasPermission('view_client')) {
                if ($request->expectsJson()) {
                    return $this->error('Unauthorized access', null, 403);
                }
                abort(403, 'Unauthorized access');
            }

            $clients = $this->clientService->getFilteredClients($request->all());

            // For AJAX/API requests, return JSON
            if ($request->expectsJson()) {
                return $this->paginated($clients, 'Clients retrieved successfully');
            }

            // For web requests, return the view
            return view('clients.index', compact('clients'));

        } catch (\Exception $e) {
            \Log::error('Failed to retrieve clients: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request' => $request->all(),
                'exception' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return $this->error('Failed to retrieve clients', $e->getMessage(), 500);
            }

            return back()->withErrors(['error' => 'Failed to retrieve clients: ' . $e->getMessage()]);
        }
    }

    public function store(CreateClientRequest $request)
    {
        try {
            if (!auth()->user()->hasPermission('create_client')) {
                if ($request->expectsJson()) {
                    return $this->error('Unauthorized access', null, 403);
                }
                return back()->withErrors(['error' => 'Unauthorized access']);
            }

            $client = $this->clientService->createClient($request->validated());

            if ($request->expectsJson()) {
                return $this->success($client, 'Client created successfully', 201);
            }

            return redirect()->route('clients.index')->with('success', 'Client created successfully');

        } catch (\Exception $e) {
            \Log::error('Failed to create client: ' . $e->getMessage(), [
                'request_data' => $request->validated(),
                'exception' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return $this->error('Failed to create client', $e->getMessage(), 500);
            }

            return back()->withErrors(['error' => 'Failed to create client: ' . $e->getMessage()])->withInput();
        }
    }

    public function show(Client $client)
    {
        try {
            if (!auth()->user()->hasPermission('view_client')) {
                return $this->error('Unauthorized access', null, 403);
            }

            $client->load(['projects.pbcRequests']);
            return $this->success($client, 'Client retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve client', $e->getMessage(), 500);
        }
    }

    public function update(UpdateClientRequest $request, Client $client)
    {
        try {
            if (!auth()->user()->hasPermission('edit_client')) {
                if ($request->expectsJson()) {
                    return $this->error('Unauthorized access', null, 403);
                }
                return back()->withErrors(['error' => 'Unauthorized access']);
            }

            $updatedClient = $this->clientService->updateClient($client, $request->validated());

            if ($request->expectsJson()) {
                return $this->success($updatedClient, 'Client updated successfully');
            }

            return redirect()->route('clients.index')->with('success', 'Client updated successfully');

        } catch (\Exception $e) {
            \Log::error('Client update failed: ' . $e->getMessage(), [
                'client_id' => $client->id,
                'request_data' => $request->validated(),
                'exception' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return $this->error('Failed to update client', $e->getMessage(), 500);
            }

            return back()->withErrors(['error' => 'Failed to update client: ' . $e->getMessage()])->withInput();
        }
    }

    public function destroy(Client $client)
    {
        try {
            if (!auth()->user()->hasPermission('delete_client')) {
                return $this->error('Unauthorized access', null, 403);
            }

            $this->clientService->deleteClient($client);
            return $this->success(null, 'Client deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete client', $e->getMessage(), 500);
        }
    }

    public function projects(Client $client)
    {
        try {
            if (!auth()->user()->hasPermission('view_project')) {
                return $this->error('Unauthorized access', null, 403);
            }

            $projects = $this->clientService->getClientProjects($client);
            return $this->success($projects, 'Client projects retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve client projects', $e->getMessage(), 500);
        }
    }

    /**
     * Export clients to Excel
     */
    public function export(Request $request)
    {
        try {
            if (!auth()->user()->hasPermission('view_client')) {
                return $this->error('Unauthorized access', null, 403);
            }

            // Implementation for export functionality
            // You can implement this later with Excel export package
            return $this->success(null, 'Export functionality coming soon');
        } catch (\Exception $e) {
            return $this->error('Failed to export clients', $e->getMessage(), 500);
        }
    }
}
