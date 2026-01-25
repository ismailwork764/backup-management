<?php

namespace App\Services;

use App\Models\StorageServer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HetznerStorageService
{
    protected $baseUrl = 'https://api.hetzner.com/v1/storage_boxes';
    protected $apiToken;

    public function __construct()
    {
        $this->apiToken = config('services.hetzner.token');
    }

    public function syncStorageBoxes()
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiToken}"
        ])->get($this->baseUrl);

        if ($response->failed()) {
            throw new \Exception('Hetzner API Error: ' . $response->body());
        }

        $data = $response->json();

        if (empty($data['storage_boxes'])) {
            return; // no boxes
        }

        foreach ($data['storage_boxes'] as $box) {
            $storageBoxId = $box['id'];

            // Fetch individual storage box to get accurate stats
            // The list endpoint might not include detailed usage stats
            $boxDetails = null;
            try {
                $boxDetails = $this->getStorageBox($storageBoxId);

            } catch (\Exception $e) {
                // If we can't fetch details, use data from list
                Log::warning('Failed to fetch storage box details for ID ' . $storageBoxId . ': ' . $e->getMessage());
                $boxDetails = $box;
            }

            // Extract usage data - try multiple possible locations
            // Note: Hetzner API returns size in bytes
            // We convert to MB first, then to GB for storage (since DB uses integer)
            // But we'll store in GB rounded, or we could store in MB for more precision
            $usedCapacityBytes = 0;
            $totalCapacityBytes = 0;

            // Try to get from detailed response first
            if (isset($boxDetails['stats']['size_data'])) {
                $usedCapacityBytes = $boxDetails['stats']['size_data'];
            } elseif (isset($boxDetails['stats']['size'])) {
                $usedCapacityBytes = $boxDetails['stats']['size'];
            } elseif (isset($box['stats']['size_data'])) {
                $usedCapacityBytes = $box['stats']['size_data'];
            }

            // Get total capacity
            if (isset($boxDetails['storage_box_type']['size'])) {
                $totalCapacityBytes = $boxDetails['storage_box_type']['size'];
            } elseif (isset($box['storage_box_type']['size'])) {
                $totalCapacityBytes = $box['storage_box_type']['size'];
            }

            // Convert bytes to GB (with 2 decimal precision)
            $usedCapacity = round($usedCapacityBytes / 1024 / 1024 / 1024, 2);

            $totalCapacity = round($totalCapacityBytes / 1024 / 1024 / 1024, 2);

            $storage_server = StorageServer::where('hetzner_id', $storageBoxId)->first();

            if (!$storage_server) {
                StorageServer::create([
                    'hetzner_id' => $storageBoxId,
                    'name' => $box['name'] ?? $boxDetails['name'] ?? 'Storage Box ' . $storageBoxId,
                    'server_address' => $box['server'] ?? $boxDetails['server'] ?? null,
                    'region' => $box['location']['name'] ?? $boxDetails['location']['name'] ?? null,
                    'api_token' => $box['username'] ?? $boxDetails['username'] ?? '',
                    'total_capacity_gb' => $totalCapacity,
                    'used_capacity_gb' => $usedCapacity,
                    'status' => $box['status'] ?? $boxDetails['status'] ?? 'active',
                ]);
            } else {
                $storage_server->update([
                    'name' => $box['name'] ?? $boxDetails['name'] ?? $storage_server->name,
                    'server_address' => $box['server'] ?? $boxDetails['server'] ?? $storage_server->server_address,
                    'region' => $box['location']['name'] ?? $boxDetails['location']['name'] ?? $storage_server->region,
                    'api_token' => $box['username'] ?? $boxDetails['username'] ?? $storage_server->api_token,
                    'total_capacity_gb' => $totalCapacity ?: $storage_server->total_capacity_gb,
                    'used_capacity_gb' => $usedCapacity,
                    'status' => $box['status'] ?? $boxDetails['status'] ?? $storage_server->status,
                ]);
            }
        }

        return redirect()->back();
    }

    public function createSubAccount(array $data): array
    {
        $storageBoxId = $data['storage_box_id'];
        // Password should already be valid UTF-8 from the generator, but ensure it's clean
        $password = $data['password'];
        $name = $data['name'] ?? null;
        $homeDirectory = 'client-' . preg_replace('/\s+/', '_', $name);

        // Prepare access settings
        $accessSettings = [
            'reachable_externally' => $data['reachable_externally'] ?? true,
            'samba_enabled' => $data['samba_enabled'] ?? true,
            'ssh_enabled' => $data['ssh_enabled'] ?? false,
            'webdav_enabled' => $data['webdav_enabled'] ?? false,
            'readonly' => $data['readonly'] ?? false,
        ];

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiToken}",
            'Content-Type' => 'application/json; charset=utf-8',
        ])->post("https://api.hetzner.com/v1/storage_boxes/{$storageBoxId}/subaccounts", [
            'password' => $password,
            'name' => $name,
            'home_directory' => $homeDirectory,
            'access_settings' => $accessSettings,
        ]);
        if ($response->failed()) {
            throw new \Exception('Hetzner API Error: ' . $response->body());
        }

        $responseData = $response->json();

        // Hetzner API returns action and subaccount objects
        // The subaccount object contains id and storage_box
        if (!isset($responseData['subaccount']['id'])) {
            throw new \Exception('Invalid Hetzner API response: missing subaccount ID. Response: ' . json_encode($responseData));
        }

        $subAccountId = $responseData['subaccount']['id'];
        $actionStatus = $responseData['action']['status'] ?? null;

        // Fetch the full subaccount details to get username and other info
        // Retry a few times if action is still running (subaccount might not be immediately available)
        $subAccountDetails = null;
        $maxRetries = 5;
        $retryDelay = 2; // seconds

        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            try {
                $subAccountDetails = $this->getSubAccount($storageBoxId, $subAccountId);
                break; // Success, exit retry loop
            } catch (\Exception $e) {
                // If action is still running and we haven't exhausted retries, wait and retry
                if ($actionStatus === 'running' && $attempt < $maxRetries - 1) {
                    sleep($retryDelay);
                    continue;
                }
                // Otherwise, rethrow the exception
                throw $e;
            }
        }

        if (!$subAccountDetails) {
            throw new \Exception('Failed to fetch subaccount details after ' . $maxRetries . ' attempts');
        }

        // Merge the password we used (API doesn't return it)
        $subAccountDetails['password'] = $password;

        return $subAccountDetails;
    }

    public function getSubAccount(int $storageBoxId, int $subAccountId): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiToken}",
        ])->get("https://api.hetzner.com/v1/storage_boxes/{$storageBoxId}/subaccounts/{$subAccountId}");

        if ($response->failed()) {
            throw new \Exception('Hetzner API Error fetching subaccount: ' . $response->body());
        }

        $responseData = $response->json();

        // Hetzner API might return the subaccount directly or nested
        if (isset($responseData['subaccount'])) {
            return $responseData['subaccount'];
        }

        return $responseData;
    }

    public function getStorageBox(int $storageBoxId): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiToken}",
        ])->get("https://api.hetzner.com/v1/storage_boxes/{$storageBoxId}");

        if ($response->failed()) {
            throw new \Exception('Hetzner API Error fetching storage box: ' . $response->body());
        }

        $responseData = $response->json();

        // Hetzner API might return the storage box directly or nested
        if (isset($responseData['storage_box'])) {
            return $responseData['storage_box'];
        }

        return $responseData;
    }

    public function deleteSubAccount(int $storageBoxId, int $subAccountId): void
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiToken}",
        ])->delete("https://api.hetzner.com/v1/storage_boxes/{$storageBoxId}/subaccounts/{$subAccountId}");

        if ($response->failed()) {
            throw new \Exception('Hetzner API Error deleting subaccount: ' . $response->body());
        }
    }

    public function getStorageBoxTypes(): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiToken}",
        ])->get('https://api.hetzner.com/v1/storage_box_types');

        if ($response->failed()) {
            throw new \Exception('Hetzner API Error fetching storage box types: ' . $response->body());
        }

        return $response->json('storage_box_types', []);
    }

    public function getLocations(): array
    {
        // Extract unique locations from storage box types pricing
        // Since Hetzner Storage Box API doesn't have a dedicated locations endpoint,
        // we get locations from where storage boxes are actually available

        try {
            $storageBoxTypes = $this->getStorageBoxTypes();

            // Map of known location IDs to their details
            $locationMap = [
                'fsn1' => ['id' => 'fsn1', 'name' => 'fsn1', 'description' => 'Falkenstein DC Park 1', 'country' => 'DE', 'city' => 'Falkenstein'],
                'hel1' => ['id' => 'hel1', 'name' => 'hel1', 'description' => 'Helsinki 1', 'country' => 'FI', 'city' => 'Helsinki'],
            ];

            $availableLocations = [];

            foreach ($storageBoxTypes as $type) {
                if (isset($type['prices']) && is_array($type['prices'])) {
                    foreach ($type['prices'] as $priceInfo) {
                        $locationId = $priceInfo['location'] ?? null;
                        if ($locationId && isset($locationMap[$locationId])) {
                            if (!isset($availableLocations[$locationId])) {
                                $availableLocations[$locationId] = $locationMap[$locationId];
                            }
                        }
                    }
                }
            }

            return array_values($availableLocations);
        } catch (\Exception $e) {
            // Fallback: return known locations
            Log::warning('Failed to extract locations from storage box types: ' . $e->getMessage());
            return [
                ['id' => 'fsn1', 'name' => 'fsn1', 'description' => 'Falkenstein DC Park 1', 'country' => 'DE', 'city' => 'Falkenstein'],
                ['id' => 'hel1', 'name' => 'hel1', 'description' => 'Helsinki 1', 'country' => 'FI', 'city' => 'Helsinki'],
            ];
        }
    }

    public function createStorageBox(array $data): array
    {
        // Prepare the request payload
        $payload = [
            'name' => $data['name'],
            'location' => $data['location'],
            'storage_box_type' => $data['storage_box_type'],
            'password' => $data['password'],
        ];

        // Add optional fields if provided
        if (!empty($data['labels'])) {
            $payload['labels'] = $data['labels'];
        }

        if (!empty($data['ssh_keys'])) {
            $payload['ssh_keys'] = $data['ssh_keys'];
        }

        if (!empty($data['access_settings'])) {
            $payload['access_settings'] = $data['access_settings'];
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiToken}",
            'Content-Type' => 'application/json; charset=utf-8',
        ])->post('https://api.hetzner.com/v1/storage_boxes', $payload);

        if ($response->failed()) {
            throw new \Exception('Hetzner API Error creating storage box: ' . $response->body());
        }

        $responseData = $response->json();

        if (!isset($responseData['storage_box']['id'])) {
            throw new \Exception('Invalid Hetzner API response: missing storage box ID. Response: ' . json_encode($responseData));
        }

        return $responseData['storage_box'];
    }

    public function deleteStorageBox(int $storageBoxId): void
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiToken}",
        ])->delete("https://api.hetzner.com/v1/storage_boxes/{$storageBoxId}");

        if ($response->failed()) {
            throw new \Exception('Hetzner API Error deleting storage box: ' . $response->body());
        }
    }
}
