<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LocalityProvinceController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function () {
    Route::post('auth/register', 'auth_register');
    Route::post('auth/login', 'auth_login');
    // Route::post('auth/account-recovery', 'auth_account_recovery');
    Route::post('auth/password-recovery', 'auth_password_recovery');
    // Route::post('auth/account-confirmation', 'auth_account_confirmation');
});

// Localities
Route::get('localities', [LocalityProvinceController::class, 'get_localities']);

// Provinces
Route::get('provinces', [LocalityProvinceController::class, 'get_provinces']);

// Users

    // Profiles
    Route::get('users_profiles', [UserController::class, 'users_profiles']);
