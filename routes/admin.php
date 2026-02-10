<?php

use App\Http\Controllers\AdvertisingReportController;
use App\Http\Controllers\AdvertisingSpaceController;
use App\Http\Controllers\AudithController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompaniesAdvertisingController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyPlanController;
use App\Http\Controllers\CompanyPlanPublicitiesReportController;
use App\Http\Controllers\CropController;
use App\Http\Controllers\ActiveIngredientController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\MagLeaseIndexController;
use App\Http\Controllers\MagSteerIndexController;
use App\Http\Controllers\MajorCropController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\InsightController;
use App\Http\Controllers\MainGrainPriceController;
use App\Http\Controllers\PriceMainActiveIngredientsProducerController;
use App\Http\Controllers\ProducerSegmentPriceController;
use App\Http\Controllers\RainfallRecordController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserCompanyController;
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
            Route::get('users/with-referrals', 'getUsersWithReferrals');
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
            Route::get('company/with-active-plans', 'companiesWithActivePlans');
            Route::get('company/status', 'companyStatus');
            Route::get('company/all-permissions', 'allPermissions');
            Route::get('company/{id}', 'show');
            Route::post('company', 'store');
            Route::post('company/{id}', 'update');
            Route::post('company/logo/{id}', 'update_logo');
        });

        // Company Plans
        Route::controller(CompanyPlanController::class)->group(function () {
            Route::get('company-plans', 'index');
            Route::get('company-plans/status', 'companyPlanStatus');
            Route::get('company-plans/{id}', 'show');
            Route::post('company-plans/status/{id}', 'updateCompanyPlanStatus');
            Route::post('company-plans', 'store');
            Route::put('company-plans/{id}', 'update');
        });

        Route::controller(UserCompanyController::class)->group(function () {
            Route::post('user-company/add-main-admin', 'add_main_admin_company_plan');
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
            Route::put('advertising-space/status/{id}', 'update_status');
        });

        // Companies Advertising
        Route::controller(CompaniesAdvertisingController::class)->group(function () {
            Route::post('advertising-companies', 'store');
            Route::post('advertising-companies/{id}', 'update');
            Route::put('advertising-companies/status/{id}', 'update_status');
        });

        // Advertising Interactions (anteriormente Reports) - Ya no se gestionan manualmente
        // Las interacciones se registran automáticamente a través de los endpoints públicos

        // faq
        Route::controller(FaqController::class)->group(function () {
            Route::post('faqs', 'store');
            Route::put('faqs/{id}', 'update');
            Route::delete('faqs/{id}', 'destroy');
        });

        // audits
        Route::controller(AudithController::class)->group(function () {
            Route::get('audiths', 'index');
        });

        // status
        Route::controller(StatusController::class)->group(function () {
            Route::get('status', 'index');
        });

        // reports
        Route::controller(ReportController::class)->group(function () {
            Route::get('status-report', 'statusReport');
        });

        Route::controller(NewsController::class)->group(function () {
            Route::get('news', 'index');
            Route::get('news/gallery', 'gallery');
            Route::post('news', 'store');
            Route::put('news/{id}', 'update');
            Route::post('news/image/{id}', 'updateImage');
            Route::delete('news/image/{id}', 'deleteImage');
            Route::put('news/{id}/status', 'changeStatus');
            Route::delete('news/{id}', 'destroy');
        });

        // MAG Lease Index
        Route::controller(MagLeaseIndexController::class)->group(function () {
            Route::get('mag-lease-index', 'index');
            Route::post('mag-lease-index', 'store');
            Route::put('mag-lease-index/{id}', 'update');
            Route::put('mag-lease-index/{id}/status', 'changeStatus');
            Route::delete('mag-lease-index/{id}', 'destroy');
        });

        // MAG Steer Index
        Route::controller(MagSteerIndexController::class)->group(function () {
            Route::get('mag-steer-index', 'index');
            Route::post('mag-steer-index', 'store');
            Route::put('mag-steer-index/{id}', 'update');
            Route::put('mag-steer-index/{id}/status', 'changeStatus');
            Route::delete('mag-steer-index/{id}', 'destroy');
        });

        // Major Crops (Perspectivas de los principales cultivos)
        Route::controller(MajorCropController::class)->group(function () {
            Route::get('major-crops', 'index');
            Route::post('major-crops', 'store');
            Route::put('major-crops/{id}', 'update');
            Route::put('major-crops/{id}/status', 'changeStatus');
            Route::delete('major-crops/{id}', 'destroy');
        });

        // Insights
        Route::controller(InsightController::class)->group(function () {
            Route::get('insights', 'index');
            Route::post('insights', 'store');
            Route::put('insights/{id}', 'update');
            Route::put('insights/{id}/status', 'changeStatus');
            Route::delete('insights/{id}', 'destroy');
        });

        // Rainfall Records (Registro de Lluvias)
        Route::controller(RainfallRecordController::class)->group(function () {
            Route::get('rainfall-records', 'index');
            Route::post('rainfall-records', 'store');
            Route::put('rainfall-records/{id}', 'update');
            Route::put('rainfall-records/{id}/status', 'changeStatus');
            Route::delete('rainfall-records/{id}', 'destroy');
        });

        // Main Grain Prices (Precios Principales Granos)
        Route::controller(MainGrainPriceController::class)->group(function () {
            Route::get('main-grain-prices', 'index');
            Route::post('main-grain-prices', 'store');
            Route::put('main-grain-prices/{id}', 'update');
            Route::put('main-grain-prices/{id}/status', 'changeStatus');
            Route::delete('main-grain-prices/{id}', 'destroy');
        });

        // Price Main Active Ingredients Producers (Precios de Ingredientes Activos)
        Route::controller(PriceMainActiveIngredientsProducerController::class)->group(function () {
            Route::get('price-main-active-ingredients-producers', 'index');
            Route::post('price-main-active-ingredients-producers', 'store');
            Route::put('price-main-active-ingredients-producers/{id}', 'update');
            Route::put('price-main-active-ingredients-producers/{id}/status', 'changeStatus');
            Route::delete('price-main-active-ingredients-producers/{id}', 'destroy');
        });

        // Producer Segment Prices (Precios por Segmento a Productor)
        Route::controller(ProducerSegmentPriceController::class)->group(function () {
            Route::get('producer-segment-prices', 'index');
            Route::post('producer-segment-prices', 'store');
            Route::put('producer-segment-prices/{id}', 'update');
            Route::put('producer-segment-prices/{id}/status', 'changeStatus');
            Route::delete('producer-segment-prices/{id}', 'destroy');
        });

        // Crops (Cultivos)
        Route::controller(CropController::class)->group(function () {
            Route::get('crops', 'index');
            Route::post('crops', 'store');
            Route::put('crops/{id}', 'update');
            Route::delete('crops/{id}', 'destroy');
        });

        // Active Ingredients (Ingredientes Activos)
        Route::controller(ActiveIngredientController::class)->group(function () {
            Route::get('active-ingredients', 'index');
            Route::post('active-ingredients', 'store');
            Route::put('active-ingredients/{id}', 'update');
            Route::delete('active-ingredients/{id}', 'destroy');
        });
    }
);
// Public FAQ route
Route::get('admin/faqs', [FaqController::class, 'index']);

//  Advertising Companies - Public route
Route::get('admin/advertising-companies', [CompaniesAdvertisingController::class, 'index']);

// Advertising Space - Public route
Route::get('admin/advertising-space', [AdvertisingSpaceController::class, 'index']);

// Advertising Interactions Statistics - Public route
Route::get('admin/advertising-interactions', [AdvertisingReportController::class, 'index']);

// Company Plan Publicities Reports
Route::get('admin/company-plan-publicities-reports', [CompanyPlanPublicitiesReportController::class, 'index']);