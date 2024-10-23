<?php

namespace App\Http\Controllers;

use App\Services\SharePointService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class SharePointController extends Controller
{
    protected $sharePointService;

    public function __construct(SharePointService $sharePointService)
    {
        $this->sharePointService = $sharePointService;
    }

    public function getData(Request $request)
    {
        // Assume you have the refresh token stored in your application
        $refreshToken = '0.AcYA2Ybh53anlE-ZmSRHCzlhrpKUvQPJ0oJJqQ7psS-om43GADA.AgABAwEAAADW6jl31mB3T7ugrWTT8pFeAwDs_wUA9P_gExjN0A-EP5tcW_8TQS3Vv418AIpe_HLou4t9SDdC44FGN9NDLLZ0d-du3QSdbdqs6qswrgcKPj1WLq-t9VQ_7ndz_lgWHqcD-jOOGKi4L1WWOk9Dao7OKCtd0hQlYzqD8-8TdWiUNPT1gmr6od1Adp8Z2mWOpZD8xcV3NJylJsw5z74v5iOkMFhQBVkawfRkTgbKSjT3dY_2VNfliuJZFvDfVHd2oOyJLctwl3moOxUi8vxDkqEH98hXn9c-bRuM6jorRsqjrERD5k6gOnx7G-WChURZLSkytLwBRH5RyrJTSJpQBz4n-mps6ZD-6C5trZ8k-gPIpjkL3wKq6C610e2o3nCrpN9Igk-f5yTwdOqrBULtdxhwDqqxrBrBA4rFf0hDWEc7srqNw186urxH771giDvCQd_5L4UEQed9m5qCiHvXVdn4Z53UqZ4ec4Ha-f36q4XSyrCP84XTig7HjLKHROW0oDszigBpWuCP41q9lgA6sXWGyenM2PVkFAJzsMEJsxXUxTiRPCoiM4vgZLrvLhWOTcKIbSEGcGIiDXWVflI2uKZ4gRDjJzJMviVi0JqGYF1SsGo7LwZL3A87f3riL0qUq1qqdLnh91a9A7ovJPdDaTYP-CwEOVLFTx9eysNUG6TVe570YEq-lKawCy95NKXiQCQUxS0z4u_pArD80Ch6jIR_Os0F8OKw_rbadT70MnmXMrL5fvHZzswn28eSyNjDumDJveOxN8cJ6OztnkNPRwmck7JDgdzcKHtEnqJuUeQWFNufpF4mCZAT5A'; // You should retrieve this from your storage

        // Get the access token
        $tokens = $this->sharePointService->getAccessToken($refreshToken);

        if (isset($tokens['error'])) {
            return response()->json(['error' => $tokens['error']], 500);
        }

        // Fetch data using the access token
        $data = $this->sharePointService->fetchData($tokens['access_token']);

        if (isset($data['error'])) {
            return response()->json(['error' => $data['error']], 500);
        }

        return response()->json($data['d']['results']);
    }

    // public function getImageFromDrive(Request $request)
    // {
    //     return $request;
    //     // $response = Http::get("https://drive.google.com/uc?export=download&id=$id");
    //     // return response($response->body(), 200)->header('Content-Type', 'image/jpeg');
    // }

    public function getImageFromDrive(Request $request)
    {
        $request->validate([
            'id' => 'required|string',
        ]);

        $id = $request->input('id');

        $filename = $id . '.jpg';

        $path = storage_path("app/public/mapImages/{$filename}");

        if (file_exists($path)) {
            return response()->file($path, [
                'Content-Type' => 'image/jpeg',
                'Content-Disposition' => 'inline; filename="' . $filename . '"'
            ]);
        }

        $response = Http::get("https://drive.google.com/uc?export=download&id=$id");

        if ($response->successful()) {

            Storage::disk('public')->put("mapImages/{$filename}", $response->body());

            if (!file_exists($path)) {
                return response()->json(['error' => 'File could not be saved'], 500);
            }

            return response()->file($path, [
                'Content-Type' => 'image/jpeg',
                'Content-Disposition' => 'inline; filename="' . $filename . '"'
            ]);
        }

        return response()->json(['error' => 'Unable to download image'], 500);
    }
}
