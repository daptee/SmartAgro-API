<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GeneralImportController;
use App\Http\Controllers\LocalityProvinceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

// Auth
Route::controller(AuthController::class)->group(function () {
    Route::post('auth/register', 'auth_register');
    Route::post('auth/login', 'auth_login');
    Route::post('auth/password-recovery', 'auth_password_recovery');
    Route::post('auth/password-recovery-token', 'auth_password_recovery_token');
});

Route::group(['middleware' => ['auth:api']], function ($router) {
    // AuthController
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('auth/password-recovery-token', [AuthController::class, 'auth_password_recovery_token']);

    // Users
    Route::controller(UserController::class)->group(function () {
        Route::post('users_change_status/{id}', 'change_status');
        Route::post('users_change_plan/{id}', 'change_plan');
        Route::put('users/update', 'update');
        Route::post('users/update/profile_picture', 'profile_picture');
    });

    // Reports
    Route::controller(ReportController::class)->group(function () {
        Route::get('reports', 'reports');
    });
});

Route::post('/import-reports', [GeneralImportController::class, 'import'])->name('import.reports');

// User profiles
Route::get('users_profiles', [UserController::class, 'users_profiles']);

// Localities
Route::get('localities', [LocalityProvinceController::class, 'get_localities']);

// Provinces
Route::get('provinces', [LocalityProvinceController::class, 'get_provinces']);

Route::get('/clear-cache', function() {
    Artisan::call('config:clear');
    Artisan::call('optimize');

    return response()->json([
        "message" => "Cache cleared successfully"
    ]);
});