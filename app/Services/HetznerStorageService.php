<?php

namespace App\Services;

use App\Models\StorageServer;
use Illuminate\Support\Facades\Http;
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

        $data = $response->json();

        if (empty($data['storage_boxes'])) {
            return; // no boxes
        }

        foreach ($data['storage_boxes'] as $box) {
            $storage_server = StorageServer::where('hetzner_id', $box['id'])->first();
            if(!$storage_server) {
                StorageServer::Create(
                    ['hetzner_id' => $box['id']],
                    [
                        'name' => $box['name'],
                        'region' => $box['location']['name'] ?? null,
                        'total_capacity_gb' => intval($box['storage_box_type']['size'] / 1024 / 1024 / 1024),
                        'used_capacity_gb' => intval($box['stats']['size_data'] / 1024 / 1024 / 1024),
                        'status' => $box['status'],
                    ]
                );
            }else{
                $storage_server->update([
                    'name' => $box['name'],
                    'region' => $box['location']['name'] ?? null,
                    'api_token' => $box['username'],
                    'total_capacity_gb' => intval($box['storage_box_type']['size'] / 1024 / 1024 / 1024),
                    'used_capacity_gb' => intval($box['stats']['size_data'] / 1024 / 1024 / 1024),
                    'status' => $box['status'],
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

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiToken}",
            'Content-Type' => 'application/json; charset=utf-8',
        ])->post("https://api.hetzner.com/v1/storage_boxes/{$storageBoxId}/subaccounts", [
            'password' => $password,
            'name' => $name,
            'home_directory' => $homeDirectory,
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
}
