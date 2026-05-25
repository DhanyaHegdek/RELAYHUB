<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Broadcast;

// Public routes — no login needed
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Protected routes — JWT required
Route::middleware('auth:api')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Users list (for New Chat modal)
    Route::get('/users', function () {
        return response()->json(
            \App\Models\User::select('id', 'name', 'email')
                ->orderBy('name')
                ->get()
        );
    });

    // Conversations & Messages
    Route::post('/conversations',               [ChatController::class, 'startConversation']);
    Route::get('/conversations',                [ChatController::class, 'getConversations']);
    Route::get('/conversations/{id}/messages',  [ChatController::class, 'getMessages']);
    Route::post('/conversations/{id}/messages', [ChatController::class, 'sendMessage']);



    Route::post('/broadcasting/auth', function (Request $request) {
        return Broadcast::auth($request);
    })->middleware('auth:api');

});