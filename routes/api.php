<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GeneralImportController;
use App\Http\Controllers\GetsFunctionsController;
use App\Http\Controllers\LocalityProvinceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

// Auth
Route::controller(AuthController::class)->group(function () {
    Route::post('auth/register', 'auth_register');
    Route::post('auth/login', 'auth_login');
    Route::post('auth/password-recovery', 'auth_password_recovery');
    Route::post('auth/password-recovery-token', 'auth_password_recovery_token');
    Route::post('auth/account-confirmation', 'auth_account_confirmation');
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
        Route::delete('users/delete', 'destroy');
        Route::post('users/update/profile_picture', 'profile_picture');
        Route::get('users/get_user_profile', 'get_user_profile');
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

Route::controller(GetsFunctionsController::class)->group(function () {
    Route::get('/countries', 'countries');
});

// Dolar API
Route::get('dolar/oficial', function() {
    $response = Http::get("https://dolarapi.com/v1/dolares/oficial");   
    if ($response->successful()) {
        return $response->json();
    } else {
        return $response->throw();
    }
});

Route::get('dolar/mayorista', function() {
    $response = Http::get("https://dolarapi.com/v1/dolares/mayorista");   
    if ($response->successful()) {
        return $response->json();
    } else {
        return $response->throw();
    }
});

Route::get('/clear-cache', function() {
    Artisan::call('config:clear');
    Artisan::call('optimize');

    return response()->json([
        "message" => "Cache cleared successfully"
    ]);
});