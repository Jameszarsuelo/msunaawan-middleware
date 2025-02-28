<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class SharePointService
{
    protected $client;
    protected $tenantId;
    protected $clientId;
    protected $clientSecret;
    protected $scope;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function getAccessToken($refreshToken, $tenantName = null)
    {

        $this->tenantId = env("{$tenantName}_SHAREPOINT_TENANT_ID");
        $this->clientId = env("{$tenantName}_SHAREPOINT_CLIENT_ID");
        $this->clientSecret = env("{$tenantName}_SHAREPOINT_CLIENT_SECRET");
        $this->scope = env("{$tenantName}_SHAREPOINT_SCOPE");

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

    public function fetchData($accessToken, $sharepointUrl)
    {
        try {
            $response = $this->client->get($sharepointUrl, [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                    'Accept' => 'application/json;odata=verbose',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['d']['results'];
        } catch (RequestException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // public function fetchData($accessToken, $sharepointUrl)
    // {
    //     $allData = [];
    //     try {
    //         do {
    //             $response = $this->client->get($sharepointUrl, [
    //                 'headers' => [
    //                     'Authorization' => "Bearer {$accessToken}",
    //                     'Accept' => 'application/json;odata=verbose',
    //                 ],
    //             ]);

    //             $data = json_decode($response->getBody()->getContents(), true);
    //             if (isset($data['d']['results'])) {
    //                 $allData = array_merge($allData, $data['d']['results']);
    //             }

    //             $sharepointUrl = $data['d']['__next'] ?? null;
    //         } while ($sharepointUrl);

    //         return $allData;
    //     } catch (RequestException $e) {
    //         // Handle exceptions or errors
    //         return ['error' => $e->getMessage()];
    //     }
    // }

}
