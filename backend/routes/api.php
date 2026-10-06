<?php

use App\Http\Controllers\Api\Admin\AlertController as AdminAlertController;
use App\Http\Controllers\Api\Admin\BackupController as AdminBackupController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SiteContentController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/check-email', [AuthController::class, 'checkEmail']);
    Route::post('/verify-code', [AuthController::class, 'verifyCode']);
    Route::post('/activate', [AuthController::class, 'activate']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/verify-reset-code', [AuthController::class, 'verifyResetCode']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Public routes
Route::get('/plans', [PlanController::class, 'index']);
Route::post('/contacts/{id}/verify', [ContactController::class, 'verify']);
Route::get('/site-content', [SiteContentController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::get('/auth/profile', [ProfileController::class, 'show']);
    Route::put('/auth/profile', [ProfileController::class, 'update']);
    Route::put('/auth/payment-method', [ProfileController::class, 'updatePaymentMethod']);
    Route::put('/auth/auto-renew', [ProfileController::class, 'updateAutoRenew']);
    Route::put('/auth/devices', [ProfileController::class, 'updateDevice']);

    Route::apiResource('contacts', ContactController::class);
    Route::post('/contacts/{id}/resend-verification', [ContactController::class, 'resendVerification']);

    Route::get('/alerts', [AlertController::class, 'index']);
    Route::post('/alerts/sos', [AlertController::class, 'trigger']);

    Route::post('/plans/change', [PlanController::class, 'change']);

    Route::prefix('admin')->middleware('admin')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);

        Route::get('/users', [AdminUserController::class, 'index']);
        Route::post('/users', [AdminUserController::class, 'store']);
        Route::get('/users/{id}', [AdminUserController::class, 'show']);
        Route::put('/users/{id}', [AdminUserController::class, 'update']);
        Route::delete('/users/{id}', [AdminUserController::class, 'destroy']);
        Route::post('/users/{id}/reset-password', [AdminUserController::class, 'resetPassword']);
        Route::post('/users/{id}/revert-plan', [AdminUserController::class, 'revertPlan']);
        Route::delete('/users/{userId}/contacts/{contactId}', [AdminUserController::class, 'destroyContact']);

        Route::put('/site-content', [SiteContentController::class, 'update']);
        Route::put('/plans/{planId}', [PlanController::class, 'adminUpdate']);

        Route::get('/payments', [AdminPaymentController::class, 'index']);
        Route::post('/payments/{id}/refund', [AdminPaymentController::class, 'refund']);
        Route::delete('/payments/{id}', [AdminPaymentController::class, 'destroy']);

        Route::get('/alerts', [AdminAlertController::class, 'index']);
        Route::delete('/alerts/{id}', [AdminAlertController::class, 'destroy']);

        Route::post('/backup', [AdminBackupController::class, 'create']);
    });
});
