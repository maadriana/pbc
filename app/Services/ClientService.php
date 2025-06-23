<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Pagination\LengthAwarePaginator;

class ClientService
{
    public function getFilteredClients(array $filters): LengthAwarePaginator
    {
        $query = Client::with(['projects'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sec_registration_no', 'like', "%{$search}%")
                      ->orWhere('industry_classification', 'like', "%{$search}%")
                      ->orWhere('primary_contact_name', 'like', "%{$search}%");
                });
            })
            ->when($filters['industry'] ?? null, function ($query, $industry) {
                $query->where('industry_classification', $industry);
            })
            ->when(isset($filters['is_active']), function ($query) use ($filters) {
                $query->where('is_active', $filters['is_active']);
            })
            ->orderBy($filters['sort_by'] ?? 'created_at', $filters['sort_order'] ?? 'desc');

        return $query->paginate($filters['per_page'] ?? 25);
    }

    public function createClient(array $clientData): Client
    {
        return Client::create($clientData);
    }

    public function updateClient(Client $client, array $clientData): Client
    {
        $client->update($clientData);
        return $client->fresh();
    }

    public function deleteClient(Client $client): bool
    {
        return $client->delete();
    }

    public function getClientProjects(Client $client): array
    {
        return $client->projects()
            ->with(['engagementPartner', 'manager', 'associate1', 'associate2'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    public function getClientStatistics(Client $client): array
    {
        $totalProjects = $client->projects()->count();
        $activeProjects = $client->projects()->where('status', 'active')->count();
        $completedProjects = $client->projects()->where('status', 'completed')->count();

        $totalPbcRequests = $client->getTotalPbcRequestsCount();
        $pendingPbcRequests = $client->getPendingPbcRequestsCount();

        return [
            'total_projects' => $totalProjects,
            'active_projects' => $activeProjects,
            'completed_projects' => $completedProjects,
            'total_pbc_requests' => $totalPbcRequests,
            'pending_pbc_requests' => $pendingPbcRequests,
            'completion_rate' => $totalPbcRequests > 0
                ? round((($totalPbcRequests - $pendingPbcRequests) / $totalPbcRequests) * 100, 2)
                : 0
        ];
    }
}
