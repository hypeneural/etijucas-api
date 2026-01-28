<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Api\BairroController;
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
    // Public Routes
    // =====================================================

    // Auth Routes
    Route::prefix('auth')->group(function () {
        Route::post('send-otp', [OtpController::class, 'send'])
            ->middleware('throttle:10,1'); // 10 requests per minute

        Route::post('verify-otp', [OtpController::class, 'verify'])
            ->middleware('throttle:10,1');

        Route::post('resend-otp', [OtpController::class, 'resend'])
            ->middleware('throttle:3,1'); // Stricter: 3 per minute

        Route::post('login', [AuthController::class, 'login']);
        Route::post('register', [AuthController::class, 'register']);

        Route::post('forgot-password', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLink']);
        Route::post('reset-password', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'reset']);
    });

    // Public Data (cached)
    Route::get('bairros', [BairroController::class, 'index'])
        ->middleware('cache.headers:static');

    // =====================================================
    // Forum Public Routes (no auth required, with optional auth)
    // =====================================================
    Route::prefix('forum')->middleware('throttle:forum')->group(function () {
        // Public read endpoints - supports optional auth for liked/saved status
        Route::get('topics', [\App\Http\Controllers\Api\Forum\TopicController::class, 'index']);
        Route::get('topics/{topic}', [\App\Http\Controllers\Api\Forum\TopicController::class, 'show']);
        Route::get('topics/{topic}/comments', [\App\Http\Controllers\Api\Forum\CommentController::class, 'index']);
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

            // Forum Moderation
            Route::post('forum/topics/{topic}/hide', [\App\Http\Controllers\Admin\AdminForumController::class, 'hideTopic']);
            Route::post('forum/users/{user}/suspend', [\App\Http\Controllers\Admin\AdminForumController::class, 'suspendUser']);
        });

        // =====================================================
        // Forum Authenticated Routes
        // =====================================================
        Route::prefix('forum')->middleware('throttle:forum')->group(function () {
            // Topics CRUD
            Route::post('topics', [\App\Http\Controllers\Api\Forum\TopicController::class, 'store']);
            Route::put('topics/{topic}', [\App\Http\Controllers\Api\Forum\TopicController::class, 'update']);
            Route::delete('topics/{topic}', [\App\Http\Controllers\Api\Forum\TopicController::class, 'destroy']);

            // Topic interactions
            Route::post('topics/{topic}/like', [\App\Http\Controllers\Api\Forum\TopicLikeController::class, 'toggle']);
            Route::post('topics/{topic}/save', [\App\Http\Controllers\Api\Forum\SavedTopicController::class, 'toggle']);
            Route::post('topics/{topic}/report', [\App\Http\Controllers\Api\Forum\ReportController::class, 'reportTopic']);

            // Comments
            Route::post('topics/{topic}/comments', [\App\Http\Controllers\Api\Forum\CommentController::class, 'store']);
            Route::delete('topics/{topic}/comments/{comment}', [\App\Http\Controllers\Api\Forum\CommentController::class, 'destroy']);

            // Comment interactions
            Route::post('comments/{comment}/like', [\App\Http\Controllers\Api\Forum\CommentLikeController::class, 'toggle']);
            Route::post('comments/{comment}/report', [\App\Http\Controllers\Api\Forum\ReportController::class, 'reportComment']);

            // Upload
            Route::post('upload', [\App\Http\Controllers\Api\Forum\ForumUploadController::class, 'store']);

            // Saved topics
            Route::get('saved', [\App\Http\Controllers\Api\Forum\SavedTopicController::class, 'index']);
        });
    });
});

