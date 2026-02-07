<?php

use App\Http\Controllers\AdvertisingReportController;
use App\Http\Controllers\AdvertisingSpaceController;
use App\Http\Controllers\AdvertisingStatusController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CacheController;
use App\Http\Controllers\CompaniesAdvertisingController;
use App\Http\Controllers\CompanyCategoryController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyPlanController;
use App\Http\Controllers\CompanyPlanPublicitiesReportController;
use App\Http\Controllers\CompanyPlanPublicityController;
use App\Http\Controllers\CompanyRolesController;
use App\Http\Controllers\CropController;
use App\Http\Controllers\ActiveIngredientController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\GeneralImportController;
use App\Http\Controllers\GetsFunctionsController;
use App\Http\Controllers\IconController;
use App\Http\Controllers\LocalityProvinceController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\ReferredController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\SegmentController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResearchOnDemand;
use App\Http\Controllers\UserCompanyController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\CheckPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

// Backup
Route::get('/backup', [BackupController::class, 'createBackup'])->name('backup');

// update payment
Route::get('/cron-payment', [SubscriptionController::class, 'cronPayment'])->name('cron-payment');

// sync payment history
Route::get('/sync-payment-history', [SubscriptionController::class, 'syncPaymentHistory'])->name('sync-payment-history');

// generate payment system documentation PDF
Route::get('/generate-payment-docs-pdf', [App\Http\Controllers\DocumentationController::class, 'generatePaymentSystemPDF'])->name('generate-payment-docs-pdf');

// finalize expired plans and advertisings
Route::get('/finalize-expired', [CompanyPlanController::class, 'finalizeExpired'])->name('finalize-expired');

// faq sin token
Route::get('faqs', [FaqController::class, 'index']);

// Auth
Route::controller(AuthController::class)->group(function () {
    Route::post('auth/register', 'auth_register');
    Route::post('auth/login', 'auth_login');
    Route::post('auth/password-recovery', 'auth_password_recovery');
    Route::post('auth/password-recovery-token', 'auth_password_recovery_token');
    Route::post('auth/account-confirmation', 'auth_account_confirmation');
    Route::post('auth/resend-welcome-email', 'resend_welcome_email');
    Route::get('auth/check-invitation/{id}', 'check_invitation');
});

Route::group(['middleware' => ['token']], function ($router) {
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
        Route::get('status-report', 'statusReport');
    });

    // Subscription
    Route::controller(SubscriptionController::class)->group(function () {
        Route::post('subscription', 'subscription');
        Route::get('subscription/check', 'subscription_check');
        Route::get('subscription/cancel', 'subscription_cancel');
        Route::get('subscription/history', 'subscription_history');
        Route::get('subscription/payment/history', 'subscription_plan');
    });

    // Company
    Route::controller(CompanyController::class)->group(function () {
        Route::get('company', 'index');
        Route::get('company/{id}', 'show');
        Route::post('company', 'store');
        Route::post('company/{id}', 'update');
        Route::post('company/logo/{id}', 'update_logo');
        Route::get('company/permissions/all', 'allPermissions');
    });

    Route::controller(CompanyCategoryController::class)->group(function () {
        Route::get('company-category', 'index');
        Route::post('company-category', 'store');
        Route::put('company-category/{id}', 'update');
    });

    Route::controller(CompanyRolesController::class)->group(function () {
        Route::get('company-roles', 'index');
        Route::post('company-roles', 'store');
        Route::put('company-roles/{id}', 'update');
    });

    Route::controller(UserCompanyController::class)->group(function () {
        Route::get('user-company', 'index');
        Route::get('user-company/invitation/list', 'list_invitations');
        Route::get('user-company/invitation/status', 'status_invitations');
        Route::get('user-company/invitation/{id}', 'show');
        Route::post('user-company/invitation/send', 'send_invitation');
        Route::post('user-company/invitation/resend', 'resend_invitation');
        Route::post('user-company/invitation/accept', 'accept_invitation');
        Route::delete('user-company/invitation/cancel/{id}', 'cancel_invitation');
        Route::delete('user-company/unassociate/{userId}/{companyId}', 'unassociate_user');
    });

    Route::controller(CompanyPlanPublicityController::class)->group(function () {
        Route::get('company-plan-publicities/{id}', 'index');
        Route::post('company-plan-publicities', 'upsertAll');
        Route::post('company-plan-publicities/settings/{id}', 'toggleGlobalAds');
    });

    // Advertising Interactions (anteriormente Reports)
    Route::controller(AdvertisingReportController::class)->group(function () {
        Route::post('advertising-reports/clicks/{id_company_advertising}', 'reportsClicks');
        Route::post('advertising-reports/impressions/{id_company_advertising}', 'reportsImpressions');
        Route::get('advertising-reports/interactions/{id_company_advertising}', 'getInteractionHistory');
    });

    // Company plan publicities Reports
    Route::controller(CompanyPlanPublicitiesReportController::class)->group(function () {
        Route::post('company-plan-publicities-reports/clicks/{id_company_plan_publicity}', 'reportsClicks');
        Route::post('company-plan-publicities-reports/impressions/{id_company_plan_publicity}', 'reportsImpressions');
    });

    // Regions
    Route::controller(RegionController::class)->group(function () {
        Route::get('regions', 'get_regions');
    });

    // Referred
    Route::controller(ReferredController::class)->group(function () {
        Route::get('referred/{id}', 'index');
        Route::post('referred/{id}', 'store');
        Route::put('referred/{id}', 'update');
        Route::post('referred/add-code/{user_id}', 'addReferralCode');
    });

    // Icons
    Route::controller(IconController::class)->group(function () {
        Route::get('icons', 'index');
        Route::get('icons/{id}', 'show');
        Route::post('icons', 'store');
        Route::post('icons/{id}', 'update');
        Route::delete('icons/{id}', 'destroy');
    });
});

//Habilitar, dentro de nuestra API, un grupo de peticiones que sean /api/company/... Estas peticiones deben tener authenticacion por APIKEY en lugar de token, para poder identificar a la empresa.

Route::group(['middleware' => ['company_api_key']], function () {
    Route::controller(CompanyController::class)->group(function () {
        // Información de mercado
        Route::get('companies/news', 'news');
        Route::get('companies/insights', 'insights');
        Route::get('companies/mag-lease-index', 'mag_lease_index');
        Route::get('companies/mag-steer-index', 'mag_steer_index');
        Route::get('companies/major-crops', 'major_crops');
        Route::get('companies/price-main-active-ingredients-producers', 'price_main_active_ingredients_producers');
        Route::get('companies/producer-segment-prices', 'producer_segment_prices');
        Route::get('companies/rainfall-records-provinces', 'rainfall_records_provinces');
        Route::get('companies/main-grain-prices', 'main_grain_prices');

        // Indicadores comerciales
        Route::get('companies/pit-indicators', 'pit_indicators');
        Route::get('companies/livestock-input-output-ratios', 'livestock_input_output_ratios');
        Route::get('companies/agricultural-input-output-relationships', 'agricultural_input_output_relationships');
        Route::get('companies/gross-margins-trend', 'gross_margins_trend');
        Route::get('companies/harvest-prices', 'harvest_prices');
        Route::get('companies/product-prices', 'product_prices');
        Route::get('companies/gross-margins', 'gross_margins');
        Route::get('companies/main-crops-buying-selling-traffic-light', 'main_crops_buying_selling_traffic_light');
    });
});

Route::post('research-on-demand', [ResearchOnDemand::class, 'research_on_demand']);
Route::post('/import-reports', [GeneralImportController::class, 'import'])->name('import.reports');
Route::post('/import-business-indicators', [GeneralImportController::class, 'import_business_indicators']);
Route::post('/notification-users-report', [ReportController::class, 'notification_users_report']);

// Delete report
Route::delete('reports', [ReportController::class, 'deleteReports']);
Route::delete('business-indicators', [ReportController::class, 'deleteBusinessIndicators']);

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

// Advertising
Route::get('/advertising-status', [AdvertisingStatusController::class, 'index']);
Route::get('/advertising-space', [AdvertisingSpaceController::class, 'index']);
Route::get('/advertising-companies', [CompaniesAdvertisingController::class, 'index']);
Route::get('/advertising-interactions', [AdvertisingReportController::class, 'index']);

// Company Plan Publicity Report
Route::get('/company-plan-publicities-reports', [CompanyPlanPublicitiesReportController::class, 'index']);

// Segments
Route::get('segments', [SegmentController::class, 'index']);

// Crops
Route::get('crops', [CropController::class, 'index']);

// Active Ingredients
Route::get('active-ingredients', [ActiveIngredientController::class, 'index']);

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
    Artisan::call('cache:clear');
    Artisan::call('config:cache');
    Artisan::call('route:cache');
    Artisan::call('view:cache');

    return response()->json([
        "message" => "Cache cleared successfully"
    ]);
});

Route::post('/webhooks/mercadopago', [SubscriptionController::class, 'handleWebhook'])->name('webhook.mercadopago');
