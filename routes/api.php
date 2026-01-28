<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\OtpController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

// =====================================================
// API v1 Routes
// =====================================================
Route::prefix('v1')->group(function () {

    // =====================================================
    // Public Auth Routes
    // =====================================================
    Route::prefix('auth')->group(function () {
        Route::post('send-otp', [OtpController::class, 'send'])
            ->middleware('throttle:10,1'); // 10 requests per minute

        Route::post('verify-otp', [OtpController::class, 'verify'])
            ->middleware('throttle:10,1');

        Route::post('register', [AuthController::class, 'register']);
    });

    // =====================================================
    // Authenticated Routes
    // =====================================================
    Route::middleware('auth:sanctum')->group(function () {

        // Auth Routes
        Route::prefix('auth')->group(function () {
            Route::post('refresh', [AuthController::class, 'refresh']);
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });

        // User Profile Routes
        Route::prefix('users')->group(function () {
            Route::get('me', [UserController::class, 'show']);
            Route::put('me', [UserController::class, 'update']);
            Route::post('me/avatar', [UserController::class, 'uploadAvatar']);
            Route::delete('me/avatar', [UserController::class, 'deleteAvatar']);
            Route::put('me/notifications', [UserController::class, 'updateNotifications']);
        });

        // =====================================================
        // Admin Routes (requires admin or moderator role)
        // =====================================================
        Route::prefix('admin')->middleware('role:admin|moderator')->group(function () {
            Route::apiResource('users', AdminUserController::class);
            Route::post('users/{user}/roles', [AdminUserController::class, 'assignRoles'])
                ->middleware('role:admin'); // Only admins can assign roles
        });
    });
});
