<?php

use App\Http\Controllers\SharePointController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('getMapLists', [SharePointController::class, 'getData']);
Route::get('getEvents', [SharePointController::class, 'getEvents']);
Route::post('getImageFromDrive', [SharePointController::class, 'getImageFromDrive']);
Route::post('getImageFromSharepoint', [SharePointController::class, 'getImageFromSharepoint']);
