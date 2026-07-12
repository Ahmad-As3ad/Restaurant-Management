<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\NotificationController;






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
            1|p5WpVmKS3JTT15bjWsdhRTdkvjzNCX58rkjXnKwW05ff20ff
*/

Route::post('/login', [AuthController::class, 'login']);



Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'user']);
});





Route::apiResource('meals', MealController::class);





Route::middleware('auth:sanctum')->group(function () {
    // Wallet routes
    Route::prefix('wallet')->group(function () {
        Route::get('/balance', [WalletController::class, 'balance']);
        Route::post('/deposit', [WalletController::class, 'deposit']);
        Route::get('/transactions', [WalletController::class, 'transactions']);
        Route::get('/transactions/{type}', [WalletController::class, 'transactionsByType']);
    });
});


Route::middleware('auth:sanctum')->group(function () {
    // Order routes
    Route::prefix('orders')->group(function () {
        Route::post('/create', [OrderController::class, 'create']);
        Route::post('/cancel', [OrderController::class, 'cancel']);
        Route::get('/my-orders', [OrderController::class, 'userOrders']);
        Route::get('/{id}', [OrderController::class, 'show']);
    });
});





Route::middleware('auth:sanctum')->group(function () {
    // Booking routes
    Route::prefix('bookings')->group(function () {
        Route::post('/create', [BookingController::class, 'create']);
        Route::post('/cancel', [BookingController::class, 'cancel']);
        Route::get('/my-bookings', [BookingController::class, 'userBookings']);
        Route::get('/upcoming', [BookingController::class, 'upcomingBookings']);
        Route::get('/{id}', [BookingController::class, 'show']);
    });
});





Route::middleware('auth:sanctum')->group(function () {
    // Notification routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread', [NotificationController::class, 'unread']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
        Route::delete('/', [NotificationController::class, 'destroyAll']);
    });
});
=======
Route::apiResource('meals', MealController::class);
