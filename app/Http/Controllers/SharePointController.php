<?php

namespace App\Http\Controllers;

use App\Services\SharePointService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class SharePointController extends Controller
{
    protected $sharePointService;

    protected $refreshToken;

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


        $sharepointUrl = "{$baseUrl}?\${$queryString}";

        // return $sharepointUrl;

        if (strpos($baseUrl, 'msuatnaawan.sharepoint.com') !== false) {
            $this->refreshToken = env('NAAWAN_SHAREPOINT_REFRESH_TOKEN');
        } else if (strpos($baseUrl, 'msugensan2.sharepoint.com') !== false) {
            $this->refreshToken = env('GENSAN_SHAREPOINT_REFRESH_TOKEN');
        }

        $refreshToken = $this->refreshToken;

        $tokens = $this->sharePointService->getAccessToken($refreshToken, $baseUrl);


        if (isset($tokens['error'])) {
            return response()->json(['error' => $tokens['error']], 500);
        }

        $data = $this->sharePointService->fetchData($tokens['access_token'], $sharepointUrl);

        if (isset($data['error'])) {
            return response()->json(['error' => $data['error']], 500);
        }

        return response()->json($data);
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

        $this->getImageFromLocal($id, $path);

        if (strpos($imagePath, 'FacultyDataImages') !== false) {
            $refreshToken = env('GENSAN_SHAREPOINT_REFRESH_TOKEN');
            $urlSharepoint = "https://msugensan2.sharepoint.com/sites/msugensan/_api/web/GetFileByServerRelativeUrl('$url')/\$value";
            $tokens = $this->sharePointService->getAccessToken($refreshToken, 'msugensan2.sharepoint.com');

        } else {
            $refreshToken = env('NAAWAN_SHAREPOINT_REFRESH_TOKEN');
            $urlSharepoint = "https://msuatnaawan.sharepoint.com/_api/web/GetFileByServerRelativeUrl('$url')/\$value";
            $tokens = $this->sharePointService->getAccessToken($refreshToken);
        }

        // Get the access token

        if (isset($tokens['error'])) {
            return response()->json(['error' => $tokens['error']], 500);
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$tokens['access_token']}", // Add your access token
            'Accept' => 'application/json',
        ])->get($urlSharepoint);


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

    public function getImageFromLocal($id, $path)
    {
        if (file_exists($path)) {
            return response()->file($path, [
                'Content-Type' => 'image/jpeg',
                'Content-Disposition' => "inline; filename='{$id}'"
            ]);
        }
    }

    public function getEvents()
    {
        // $sharepointUrl = "https://msuatnaawan.sharepoint.com/sites/MSUatNaawan/_api/web/lists/GetByTitle('Events')/items?&\$select=Title,Description,Location,ContentTypeId,EventDate,EndDate,ItemLink,BodyText,WPLink,EventListName,ImageURL&\$filter=DisplayToPublic eq 1";

        $sharepointUrl = [
            "baseUrl" => "https://msuatnaawan.sharepoint.com/sites/MSUatNaawan/_api/web/lists/GetByTitle('Events')/items",
            "queryParams" => [
                "select" => "Title,Description,Location,ContentTypeId,EventDate,EndDate,ItemLink,BodyText,WPLink,EventListName,ImageURL",
                "filter" => "DisplayToPublic eq 1"
            ],
        ];

        $data = $this->getDataFromResponse($sharepointUrl);

        return $data;

    }

    public function getNews()
    {
        // $sharepointUrl = "https://msuatnaawan.sharepoint.com/_api/web/lists/GetByTitle('Events')/items?select=Title,Location,Description,EventDate,EndDate";
        // $sharepointUrl = "https://msuatnaawan.sharepoint.com/sites/MSUatNaawan/_api/web/lists/GetByTitle('News')/items?&\$select=Title,Description,ImageURL,Created,ItemLink,BodyText,WPLink&\$filter=DisplayToPublic eq 1";

        $sharepointUrl = [
            "baseUrl" => "https://msuatnaawan.sharepoint.com/sites/MSUatNaawan/_api/web/lists/GetByTitle('News')/items",
            "queryParams" => [
                "select" => "Title,Description,ImageURL,Created,ItemLink,BodyText,WPLink",
                "filter" => "DisplayToPublic eq 1"
            ],
        ];

        $data = $this->getDataFromResponse($sharepointUrl);

        return $data;

    }

    public function getAnnouncements()
    {
        // $sharepointUrl = "https://msuatnaawan.sharepoint.com/_api/web/lists/GetByTitle('Events')/items?select=Title,Location,Description,EventDate,EndDate";
        $sharepointUrl = "https://msuatnaawan.sharepoint.com/sites/MSUatNaawan/_api/web/lists/GetByTitle('Announcements')/items?&\$select=Title,Description,ImageURL,Created,ItemLink,BodyText,WPLink&\$filter=DisplayToPublic eq 1";

        $sharepointUrl = [
            "baseUrl" => "https://msuatnaawan.sharepoint.com/sites/MSUatNaawan/_api/web/lists/GetByTitle('Announcements')/items",
            "queryParams" => [
                "select" => "Title,Description,ImageURL,Created,ItemLink,BodyText,WPLink",
                "filter" => "DisplayToPublic eq 1"
            ],
        ];

        $data = $this->getDataFromResponse($sharepointUrl);


        return $data;

    }

    public function getResearch()
    {
        // $sharepointUrl = "https://msuatnaawan.sharepoint.com/_api/web/lists/GetByTitle('Events')/items?select=Title,Location,Description,EventDate,EndDate";
        $sharepointUrl = "https://msuatnaawan.sharepoint.com/sites/MSUatNaawan/_api/web/lists/GetByTitle('Research')/items?&\$select=Title,Description,ImageURL,Created,ItemLink,BodyText,WPLink&\$filter=DisplayToPublic eq 1";

        $sharepointUrl = [
            "baseUrl" => "https://msuatnaawan.sharepoint.com/sites/MSUatNaawan/_api/web/lists/GetByTitle('Research')/items",
            "queryParams" => [
                "select" => "Title,Description,ImageURL,Created,ItemLink,BodyText,WPLink",
                "filter" => "DisplayToPublic eq 1"
            ],
        ];

        $data = $this->getDataFromResponse($sharepointUrl);

        return $data;

    }

    public function getDataFromResponse($sharepointUrl)
    {
        // Create a new Laravel request instance with the given data
        $request = new Request($sharepointUrl);

        // Call the getData function
        $response = $this->getData($request);

        // Convert response to array
        $responseData = $response->getData(true);

        // Debug the response data structure
        return response()->json($responseData);
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
