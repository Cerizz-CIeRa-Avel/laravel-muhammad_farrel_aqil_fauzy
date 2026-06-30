<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

// Public Routes (Tidak perlu token)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Protected Routes (Harus Login / Punya Token Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth & Verifikasi
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail']);

    // Route untuk mengambil data user login saat ini
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // --- IMPLEMENTASI MIDDLEWARE ROLE & PERMISSION SPATIE ---
    
    // Hanya bisa diakses oleh Super Admin
    Route::middleware('role:Super Admin')->group(function () {
        Route::get('/superadmin/dashboard', function () {
            return response()->json(['message' => 'Welcome Super Admin']);
        });
    });

    // Bisa diakses oleh Super Admin ATAU Admin
    Route::middleware('role:Super Admin|Admin')->group(function () {
        Route::get('/admin/dashboard', function () {
            return response()->json(['message' => 'Welcome Admin/Super Admin']);
        });
    });

    // Bisa diakses oleh Staff
    Route::middleware('role:Staff')->group(function () {
        Route::get('/staff/task', function () {
            return response()->json(['message' => 'Ini area Staff']);
        });
    });

    // Bisa diakses oleh Customer
    Route::middleware('role:Customer')->group(function () {
        Route::get('/customer/profile', function () {
            return response()->json(['message' => 'Ini profil Customer']);
        });
    });

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return response()->json(['message' => 'Email berhasil diverifikasi.']);
    })->middleware(['auth:sanctum', 'signed'])->name('verification.verify');
});
