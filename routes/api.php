<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;

//instead of default sanctum auth, we will use JWT auth for API routes
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);


//Everything inside this group requires JWT authentication
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);
}); 


Route::middleware('auth:api')->group(function () {
    Route::post('/conversations', [ChatController::class, 'startConversation']);
    Route::get('/conversations', [ChatController::class, 'getConversations']);
    Route::get('/conversations/{id}/messages', [ChatController::class, 'getMessages']);
    Route::post('/conversations/{id}/messages', [ChatController::class, 'sendMessage']);
});