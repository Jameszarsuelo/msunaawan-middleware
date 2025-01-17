<?php

namespace App\Http\Controllers;

use App\Services\SharePointService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class SharePointController extends Controller
{
    protected $sharePointService;

    protected $refreshToken = '1.AcYA2Ybh53anlE-ZmSRHCzlhrpKUvQPJ0oJJqQ7psS-om43GAG_GAA.AgABAwEAAADW6jl31mB3T7ugrWTT8pFeAwDs_wUA9P9NIQfNcJ_JI6yuUhz5TQidC_iBWX3QWBeZ-XVip6CYuWbTx1m0xqq3X0ssrto75Olw7owg8azpyXXfhtDICsL7UA-Gt0akBC6xS6sfGWTZiyLtmV5aI_MhINACf9lTsn2RjoRdBUm8Zub0mHUIRQ0O2wNZfA_0t0exsN4BbmzLq3BbqtbXP6Z77DYoc8Nb_jEh42c7ECOsRhQlA7dYvdDaJQCwriS-chTwXE4Ur9mRfsXh7SSE-iihDEs0W7YyZNgzd6sm6Y0SHosueFgbIao4oVQl3edhQe1EWUBslTwGjcIUMWPFQa60edaUhrCjtEzOcBtE8DRBbdhcUijgb_e7O2MoT7yB5fWQd3G5hPD2JiHwAuw0zJqzrdMMElHpDJ7tbTa3mVdsmoPTJajL3OWZNMkdnBFlCd2j5SxjXlrfLhLwS2GyvGchzojdedkLbrzN1QH6riYOX_RG8bf-quTbu0betAohGDURw0fHg6SzVh7SbWhz-CnhdZrqtjwXtPxWRaVR4nKHFBIJlRgM47CMgoQ_XsmMHZFPzDi6f6tNHp83_p0no9FDWLg_0t-eSDJw_m2MB6ga4H5qrvVUmAIczxtueXkDQd3ef3ChBcI8YA2DFq1Via01dF8YidsSz2u9NV9dgYRI5dGzYcFXgwhvGgPS9bR5jUroCK9IMsEOmDJX4t10fd5w0JUKaxp6_YEiZq13OfDQa3x3Jq76tocnY5oO7wkLfyU-MCr6b-6BNu5vH4r3y6G0HWjQpHrgyeBLjp3LJ3lz9tbokrdk-uszKnLI_QIx91K1laGn2C3OWMGLrg';

    public function __construct(SharePointService $sharePointService)
    {
        $this->sharePointService = $sharePointService;
    }

    public function getData(Request $request)
    {

        $baseUrl = $request->input('baseUrl');
        $queryParams = $request->input('queryParams');

        $queryString = http_build_query($queryParams, '', '&$', PHP_QUERY_RFC3986);
        // $replaceString = str_replace("\/", '/', $queryString);

        $sharepointUrl = "{$baseUrl}?{$queryString}";

        // return $sharepointUrl;

        $refreshToken = $this->refreshToken;

        $tokens = $this->sharePointService->getAccessToken($refreshToken);

        if (isset($tokens['error'])) {
            return response()->json(['error' => $tokens['error']], 500);
        }

        $data = $this->sharePointService->fetchData($tokens['access_token'], $sharepointUrl);

        if (isset($data['error'])) {
            return response()->json(['error' => $data['error']], 500);
        }

        return response()->json($data['d']['results']);
    }

    // public function getImageFromDrive(Request $request)
    // {
    //     $request->validate([
    //         'id' => 'required|string',
    //     ]);

    //     $id = $request->input('id');

    //     $filename = $id . '.jpg';

    //     $path = storage_path("app/public/mapImages/{$filename}");

    //     if (file_exists($path)) {
    //         return response()->file($path, [
    //             'Content-Type' => 'image/jpeg',
    //             'Content-Disposition' => 'inline; filename="' . $filename . '"'
    //         ]);
    //     }

    //     $response = Http::get("https://drive.google.com/uc?export=download&id=$id");

    //     if ($response->successful()) {

    //         Storage::disk('public')->put("mapImages/{$filename}", $response->body());

    //         if (!file_exists($path)) {
    //             return response()->json(['error' => 'File could not be saved'], 500);
    //         }

    //         // return response()->file($path, [
    //         //     'Content-Type' => 'image/jpeg',
    //         //     'Content-Disposition' => 'inline; filename="' . $filename . '"'
    //         // ]);
    //         return response($response->body(), 200)->header('Content-Type', 'image/jpeg');
    //     }

    //     return response()->json(['error' => 'Unable to download image'], 500);
    // }

    public function getImageFromSharepoint(Request $request)
    {

        $request->validate([
            'id' => 'required|string',
        ]);

        $id = $request->input('id');
        $imagePath = $request->input('imagePath');

        $url = "{$imagePath}{$id}";

        $path = storage_path("app/public/mapImages/{$id}");

        if (file_exists($path)) {
            return response()->file($path, [
                'Content-Type' => 'image/jpeg',
                'Content-Disposition' => "inline; filename='{$id}'"
            ]);
        }

        // Assume you have the refresh token stored in your application
        $refreshToken = $this->refreshToken;

        // Get the access token
        $tokens = $this->sharePointService->getAccessToken($refreshToken);

        if (isset($tokens['error'])) {
            return response()->json(['error' => $tokens['error']], 500);
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$tokens['access_token']}", // Add your access token
            'Accept' => 'application/json',
        ])->get("https://msuatnaawan.sharepoint.com/_api/web/GetFileByServerRelativeUrl('$url')/\$value");

        if ($response->successful()) {

            Storage::disk('public')->put("mapImages/{$id}", $response->body());

            if (!file_exists($path)) {
                return response()->json(['error' => 'File could not be saved'], 500);
            }

            return response($response->body(), 200)
                ->header('Content-Type', 'image/jpeg'); // Adjust depending on the image type
        } else {
            return response()->json(['error' => 'Failed to fetch the image from SharePoint'], 500);
        }
    }

    public function getEvents()
    {
        // $sharepointUrl = "https://msuatnaawan.sharepoint.com/_api/web/lists/GetByTitle('Events')/items?select=Title,Location,Description,EventDate,EndDate";
        $sharepointUrl = "https://msuatnaawan.sharepoint.com/sites/MSUatNaawan/_api/web/lists/GetByTitle('Events')/items?&\$select=Title,Description,Location,ContentTypeId,EventDate,EndDate,ItemLink,BodyText,WPLink,EventListName,ImageURL&\$filter=DisplayToPublic eq 1";

        $result = $this->sharepointData($sharepointUrl);

        return response()->json($result['d']['results']);

    }

    public function getNews()
    {
        // $sharepointUrl = "https://msuatnaawan.sharepoint.com/_api/web/lists/GetByTitle('Events')/items?select=Title,Location,Description,EventDate,EndDate";
        $sharepointUrl = "https://msuatnaawan.sharepoint.com/sites/MSUatNaawan/_api/web/lists/GetByTitle('News')/items?&\$select=Title,Description,ImageURL,Created,ItemLink,BodyText,WPLink&\$filter=DisplayToPublic eq 1";

        $result = $this->sharepointData($sharepointUrl);

        return response()->json($result['d']['results']);

    }

    public function getAnnouncements()
    {
        // $sharepointUrl = "https://msuatnaawan.sharepoint.com/_api/web/lists/GetByTitle('Events')/items?select=Title,Location,Description,EventDate,EndDate";
        $sharepointUrl = "https://msuatnaawan.sharepoint.com/sites/MSUatNaawan/_api/web/lists/GetByTitle('Announcements')/items?&\$select=Title,Description,ImageURL,Created,ItemLink,BodyText,WPLink&\$filter=DisplayToPublic eq 1";

        $result = $this->sharepointData($sharepointUrl);

        return response()->json($result['d']['results']);

    }

    public function getResearch()
    {
        // $sharepointUrl = "https://msuatnaawan.sharepoint.com/_api/web/lists/GetByTitle('Events')/items?select=Title,Location,Description,EventDate,EndDate";
        $sharepointUrl = "https://msuatnaawan.sharepoint.com/sites/MSUatNaawan/_api/web/lists/GetByTitle('Research')/items?&\$select=Title,Description,ImageURL,Created,ItemLink,BodyText,WPLink&\$filter=DisplayToPublic eq 1";

        $result = $this->sharepointData($sharepointUrl);

        return response()->json($result['d']['results']);

    }

    public function getUsers()
    {
        $sharepointUrl = "https://msuatnaawan.sharepoint.com/sites/MSUatNaawan/_api/web/siteusers";

        $result = $this->sharepointData($sharepointUrl);

        // return response()->json($result['d']['results']);
        return response()->json($result['d']['results']);

    }

    public function sharepointData($sharepointUrl)
    {
        $refreshToken = $this->refreshToken;

        $tokens = $this->sharePointService->getAccessToken($refreshToken);

        if (isset($tokens['error'])) {
            return response()->json(['error' => $tokens['error']], 500);
        }

        $data = $this->sharePointService->fetchData($tokens['access_token'], $sharepointUrl);

        if (isset($data['error'])) {
            return response()->json(['error' => $data['error']], 500);
        }

        return $data;
    }
}
