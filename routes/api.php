<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LocalityProvinceController;
use App\Http\Controllers\NewsImportController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
        Route::get('users_profiles', 'users_profiles');
        Route::post('users_change_status/{id}', 'change_status');
        Route::post('users_change_plan/{id}', 'change_plan');
        Route::put('users/update', 'update');
        Route::post('users/update/profile_picture', 'profile_picture');
    });
});

Route::post('/import-news', [NewsImportController::class, 'import'])->name('import.news');

// Localities
Route::get('localities', [LocalityProvinceController::class, 'get_localities']);

// Provinces
Route::get('provinces', [LocalityProvinceController::class, 'get_provinces']);
