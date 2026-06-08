<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
/*
            first_name
            last_name
            phone
            address
            email
            password
            password_confirmation
*/
Route::post('/register', [AuthController::class, 'register']);



/*
            email
            password
*/
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});
