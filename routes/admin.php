<?php

use App\Http\Controllers\AdvertisingReportController;
use App\Http\Controllers\AdvertisingSpaceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompaniesAdvertisingController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyPlanController;
use App\Http\Controllers\CompanyPlanPublicitiesReportController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Middleware for admin users

Route::post('admin/auth', [AuthController::class, 'auth_login_admin']);

Route::prefix('admin')
    ->middleware(['admin'])
    ->group(function () {
        // Users
        Route::controller(UserController::class)->group(function () {
            Route::get('users', 'index');
            Route::get('users/status', 'get_user_status');
            Route::get('users/send-welcome-email/{id}', 'send_welcome_email');
            Route::get('users/{id}', 'show');
            Route::post('users/create', 'create');
            Route::post('users/edit/profile_picture', 'profile_picture_admin');
            Route::put('users/edit/{id}', 'edit');
            Route::delete('users/delete-by-id/{id}', 'destroy_by_id');
            Route::post('users/change-status/{id}', 'change_status');
        });

        // susbscriptions
        Route::controller(SubscriptionController::class)->group(function () {
            Route::get('subscription/payment/history/{id}', 'subscription_plan_by_id');
        });

        // Company
        Route::controller(CompanyController::class)->group(function () {
            Route::get('company', 'index');
            Route::get('company/{id}', 'show');
            Route::post('company', 'store');
            Route::post('company/{id}', 'update');
            Route::post('company/logo/{id}', 'update_logo');
        });

        // Company Plans
        Route::controller(CompanyPlanController::class)->group(function () {
            Route::get('company-plans', 'index');
            Route::post('company-plans', 'store');
            Route::put('company-plans/{id}', 'update');
        });

        // Company Plan Publicities Reports 
        Route::controller(CompanyPlanPublicitiesReportController::class)->group(function () {
            Route::post('company-plan-publicities-reports', 'store');
            Route::put('company-plan-publicities-reports/{id}', 'update');
        });

        // Advertising
        Route::controller(AdvertisingSpaceController::class)->group(function () {
            Route::post('advertising-space', 'store');
            Route::put('advertising-space/{id}', 'update');
        });

        // Companies Advertising
        Route::controller(CompaniesAdvertisingController::class)->group(function () {
            Route::post('advertising-companies', 'store');
            Route::post('advertising-companies/{id}', 'update');
        });

        // Advertising Reports
        Route::controller(AdvertisingReportController::class)->group(function () {
            Route::post('advertising-reports', 'store');
            Route::put('advertising-reports/{id}', 'update');
        });

        // faq
        Route::controller(FaqController::class)->group(function () {
            Route::post('faqs', 'store');
            Route::put('faqs/{id}', 'update');
            Route::delete('faqs/{id}', 'destroy');
        });
    }
);
// Public FAQ route
Route::get('admin/faqs', [FaqController::class, 'index']);

//  Advertising Companies - Public route
Route::get('admin/advertising-companies', [CompanyPlanController::class, 'index']);

// Advertising Space - Public route
Route::get('admin/advertising-space', [AdvertisingSpaceController::class, 'index']);

// Advertising Reports - Public route
Route::get('admin/advertising-reports', [AdvertisingReportController::class, 'index']);

// Company Plan Publicities Reports
Route::get('admin/company-plan-publicities-reports', [CompanyPlanPublicitiesReportController::class, 'index']);