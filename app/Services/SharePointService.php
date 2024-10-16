<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class SharePointService
{
    protected $client;
    protected $tenantId = 'e7e186d9-a776-4f94-9999-24470b3961ae';
    protected $clientId = '03bd9492-d2c9-4982-a90e-e9b12fa89b8d';
    protected $clientSecret = 'zns8Q~dWaaqufTSzdfE8JPEsP4SiNu6WDazoxdhD';
    protected $scope = 'https://msuatnaawan.sharepoint.com/.default offline_access';

    public function __construct()
    {
        $this->client = new Client();
    }

    public function getAccessToken($refreshToken)
    {
        try {
            $response = $this->client->post("https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token", [
                'form_params' => [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'scope' => $this->scope,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'access_token' => $data['access_token'] ?? null,
                'refresh_token' => $data['refresh_token'] ?? null,
            ];
        } catch (RequestException $e) {
            // Handle exceptions or errors
            return ['error' => $e->getMessage()];
        }
    }

    public function fetchData($accessToken)
    {
        try {
            $response = $this->client->get("https://msuatnaawan.sharepoint.com/_api/web/lists/GetByTitle('MSUNaawanBuildings')/items?\$select=Title,Latitude,Longitude,Description,Pictures,Status,Coordinates,is_deleted&\$filter=is_deleted eq 0", [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                    'Accept' => 'application/json;odata=verbose',
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            // Handle exceptions or errors
            return ['error' => $e->getMessage()];
        }
    }

}
