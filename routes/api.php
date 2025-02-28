<?php

use App\Http\Controllers\SharePointController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('getData', [SharePointController::class, 'getData']);
Route::get('getEvents', [SharePointController::class, 'getEvents']);
Route::get('getNews', [SharePointController::class, 'getNews']);
Route::get('getAnnouncements', [SharePointController::class, 'getAnnouncements']);
Route::get('getResearch', [SharePointController::class, 'getResearch']);
Route::get('getUsers', [SharePointController::class, 'getUsers']);
Route::post('getImageFromDrive', [SharePointController::class, 'getImageFromDrive']);
Route::post('getImageFromSharepoint', [SharePointController::class, 'getImageFromSharepoint']);
Route::post('getVideoFromSharepoint', [SharePointController::class, 'getVideoFromSharepoint']);
