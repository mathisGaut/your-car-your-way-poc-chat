<?php

use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::get('/users', [ChatController::class, 'users']);
Route::get('/conversations/{conversation}', [ChatController::class, 'show']);
Route::get('/conversations/{conversation}/messages', [ChatController::class, 'messages']);
Route::post('/conversations/{conversation}/messages', [ChatController::class, 'storeMessage']);
