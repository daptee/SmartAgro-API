<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GeneralImportController;
use App\Http\Controllers\GetsFunctionsController;
use App\Http\Controllers\LocalityProvinceController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResearchOnDemand;
use App\Http\Controllers\UserController;
use App\Http\Middleware\CheckPlan;
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
    Route::post('auth/resend-welcome-email', 'resend_welcome_email');
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
        Route::get('business-indicators', 'business_indicators')->middleware(CheckPlan::class);
    });

    // Subscription
    Route::controller(SubscriptionController::class)->group(function () {
        Route::post('subscription', 'subscription');
        Route::get('subscription/check', 'subscription_check');
    });
});

Route::post('research-on-demand', [ResearchOnDemand::class, 'research_on_demand']);
Route::post('/import-reports', [GeneralImportController::class, 'import'])->name('import.reports');
Route::post('/import-business-indicators', [GeneralImportController::class, 'import_business_indicators']);
Route::post('/notification-users-report', [ReportController::class, 'notification_users_report']);

// User profiles
Route::get('users_profiles', [UserController::class, 'users_profiles']);

// Localities
Route::get('localities', [LocalityProvinceController::class, 'get_localities']);

// Provinces
Route::get('provinces', [LocalityProvinceController::class, 'get_provinces']);

Route::controller(GetsFunctionsController::class)->group(function () {
    Route::get('/countries', 'countries');
    Route::get('/plans', 'plans');
});

// Dolar API
Route::get('dolar/oficial', function () {
    $response = Http::get("https://dolarapi.com/v1/dolares/oficial");
    if ($response->successful()) {
        return $response->json();
    } else {
        return $response->throw();
    }
});

Route::get('dolar/mayorista', function () {
    $response = Http::get("https://dolarapi.com/v1/dolares/mayorista");
    if ($response->successful()) {
        return $response->json();
    } else {
        return $response->throw();
    }
});

Route::get('dolar/blue', function () {
    $response = Http::get("https://dolarapi.com/v1/dolares/blue");
    if ($response->successful()) {
        return $response->json();
    } else {
        return $response->throw();
    }
});

Route::get('/clear-cache', function () {
    Artisan::call('config:cache');
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('optimize');

    return response()->json([
        "message" => "Cache cleared successfully"
    ]);
});

Route::get('/subscription/callback', [SubscriptionController::class, 'callback'])->name('subscription.callback');
Route::post('/webhooks/mercadopago', [SubscriptionController::class, 'handleWebhook'])->name('webhook.mercadopago');
