<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\YouTubeIntegrationController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(\App\Http\Middleware\ChromeExtensionAuth::class)->group(function () {
    Route::get('/youtube/categories', [YouTubeIntegrationController::class, 'getCategories']);
    Route::post('/youtube/store', [YouTubeIntegrationController::class, 'store']);
});
