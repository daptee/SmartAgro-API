<?php

use App\Http\Controllers\AdvertisingReportController;
use App\Http\Controllers\AdvertisingSpaceController;
use App\Http\Controllers\AudithController;
use App\Http\Controllers\BusinessIndicatorDataTransferController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassificationController;
use App\Http\Controllers\CompaniesAdvertisingController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyPlanController;
use App\Http\Controllers\CompanyPlanPublicitiesReportController;
use App\Http\Controllers\CropController;
use App\Http\Controllers\ActiveIngredientController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventImportController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\IconController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\MagLeaseIndexController;
use App\Http\Controllers\MagSteerIndexController;
use App\Http\Controllers\MajorCropController;
use App\Http\Controllers\MarketDataTransferController;
use App\Http\Controllers\MarketGeneralControlController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\InsightController;
use App\Http\Controllers\MainGrainPriceController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\PriceMainActiveIngredientsProducerController;
use App\Http\Controllers\AgriculturalInputOutputRelationshipController;
use App\Http\Controllers\BusinessIndicatorControlController;
use App\Http\Controllers\GrossMarginController;
use App\Http\Controllers\HarvestPricesController;
use App\Http\Controllers\MainCropsBuyingSellingTrafficLightController;
use App\Http\Controllers\GrossMarginsTrendController;
use App\Http\Controllers\LivestockInputOutputRatioController;
use App\Http\Controllers\PitIndicatorController;
use App\Http\Controllers\ProductPriceController;
use App\Http\Controllers\ProducerSegmentPriceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RainfallRecordController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserCompanyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EconomicVariableController;
use App\Http\Controllers\UnitOfMeasureController;
use App\Http\Controllers\UserProfileController;
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

        // Icons
        Route::controller(IconController::class)->group(function () {
            Route::get('icons', 'index');
            Route::get('icons/{id}', 'show');
            Route::post('icons', 'store');
            Route::post('icons/{id}', 'update');
            Route::delete('icons/{id}', 'destroy');
        });

        // Images
        Route::controller(ImageController::class)->group(function () {
            Route::post('images', 'store');
            Route::post('images/{id}', 'update');
            Route::delete('images/{id}', 'destroy');
        });

        // faq
        Route::controller(FaqController::class)->group(function () {
            Route::post('faqs', 'store');
            Route::put('faqs/{id}', 'update');        
            Route::put('faqs/{id}/status', 'updateStatus');
            Route::delete('faqs/{id}', 'destroy');
        });

        // Regions
        Route::controller(RegionController::class)->group(function () {
            Route::post('regions', 'store');
            Route::put('regions/{id}', 'update');
            Route::put('regions/{id}/status', 'updateStatus');
            Route::delete('regions/{id}', 'destroy');
        });

        // User Profiles
        Route::controller(UserProfileController::class)->group(function () {
            Route::post('user-profiles', 'store');
            Route::put('user-profiles/{id}', 'update');
            Route::put('user-profiles/{id}/status', 'updateStatus');
            Route::delete('user-profiles/{id}', 'destroy');
        });

        // Plans (solo edición, sin crear ni eliminar)
        Route::controller(PlanController::class)->group(function () {
            Route::put('plans/{id}', 'update');
        });

        // Classifications
        Route::controller(ClassificationController::class)->group(function () {
            Route::post('classifications', 'store');
            Route::put('classifications/{id}', 'update');
            Route::put('classifications/{id}/status', 'updateStatus');
            Route::delete('classifications/{id}', 'destroy');
        });

        // Products
        Route::controller(ProductController::class)->group(function () {
            Route::post('products', 'store');
            Route::put('products/{id}', 'update');
            Route::put('products/{id}/status', 'updateStatus');
            Route::delete('products/{id}', 'destroy');
        });

        // audits
        Route::controller(AudithController::class)->group(function () {
            Route::get('audiths', 'index');
        });

        // status
        Route::controller(StatusController::class)->group(function () {
            Route::get('status', 'index');
        });

        // Events
        Route::controller(EventController::class)->group(function () {
            Route::get('events', 'index');
            Route::get('events/{id}', 'show');
            Route::post('events', 'store');
            Route::put('events/{id}', 'update');
            Route::delete('events/{id}', 'destroy');
        });

        // Event Import
        Route::post('events/import-users', [EventImportController::class, 'import']);

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

        // Business Indicator Controls (Control general de indicadores comerciales)
        Route::controller(BusinessIndicatorControlController::class)->group(function () {
            Route::get('business-indicator-controls', 'index');
            Route::get('business-indicator-controls/{id}', 'show');
            Route::post('business-indicator-controls', 'store');
            Route::put('business-indicator-controls/{id}', 'update');
            Route::put('business-indicator-controls/{id}/data', 'updateData');
            Route::put('business-indicator-controls/{id}/status', 'changeStatus');
            Route::delete('business-indicator-controls/{id}', 'destroy');
        });

        // Livestock Input/Output Ratios (Relaciones insumo/producto ganaderas)
        Route::controller(LivestockInputOutputRatioController::class)->group(function () {
            Route::get('livestock-input-output-ratios', 'index');
            Route::post('livestock-input-output-ratios', 'store');
            Route::put('livestock-input-output-ratios/{id}', 'update');
            Route::put('livestock-input-output-ratios/{id}/status', 'changeStatus');
            Route::delete('livestock-input-output-ratios/{id}', 'destroy');
            Route::delete('livestock-input-output-ratios/duplicates/delete', 'deleteDuplicates');
        });

        // Agricultural Input/Output Relationships (Relaciones insumo/producto agrícolas)
        Route::controller(AgriculturalInputOutputRelationshipController::class)->group(function () {
            Route::get('agricultural-input-output-relationships', 'index');
            Route::post('agricultural-input-output-relationships', 'store');
            Route::put('agricultural-input-output-relationships/{id}', 'update');
            Route::put('agricultural-input-output-relationships/{id}/status', 'changeStatus');
            Route::delete('agricultural-input-output-relationships/{id}', 'destroy');
            Route::delete('agricultural-input-output-relationships/duplicates/delete', 'deleteDuplicates');
        });

        // PIT Indicators (Indicadores PIT)
        Route::controller(PitIndicatorController::class)->group(function () {
            Route::get('pit-indicators', 'index');
            Route::post('pit-indicators', 'store');
            Route::put('pit-indicators/{id}', 'update');
            Route::put('pit-indicators/{id}/status', 'changeStatus');
            Route::delete('pit-indicators/{id}', 'destroy');
        });

        // Gross Margins (Márgenes Brutos)
        Route::controller(GrossMarginController::class)->group(function () {
            Route::get('gross-margins', 'index');
            Route::post('gross-margins', 'store');
            Route::put('gross-margins/{id}', 'update');
            Route::put('gross-margins/{id}/status', 'changeStatus');
            Route::delete('gross-margins/{id}', 'destroy');
        });

        // Gross Margins Trend (Tendencia de Márgenes Brutos - Intercampaña)
        Route::controller(GrossMarginsTrendController::class)->group(function () {
            Route::get('gross-margins-trend', 'index');
            Route::post('gross-margins-trend', 'store');
            Route::put('gross-margins-trend/{id}', 'update');
            Route::put('gross-margins-trend/{id}/status', 'changeStatus');
            Route::delete('gross-margins-trend/{id}', 'destroy');
            Route::delete('gross-margins-trend/duplicates/delete', 'deleteDuplicates');
        });

        // Product Prices (Precios de productos)
        Route::controller(ProductPriceController::class)->group(function () {
            Route::get('product-prices', 'index');
            Route::post('product-prices', 'store');
            Route::put('product-prices/{id}', 'update');
            Route::put('product-prices/{id}/status', 'changeStatus');
            Route::delete('product-prices/{id}', 'destroy');
        });

        // Harvest Prices (Precios de cosecha)
        Route::controller(HarvestPricesController::class)->group(function () {
            Route::get('harvest-prices', 'index');
            Route::post('harvest-prices', 'store');
            Route::put('harvest-prices/{id}', 'update');
            Route::put('harvest-prices/{id}/status', 'changeStatus');
            Route::delete('harvest-prices/{id}', 'destroy');
            Route::delete('harvest-prices/duplicates/delete', 'deleteDuplicates');
        });

        // Main Crops Buying/Selling Traffic Light (Semáforo de compra/venta de cultivos)
        Route::controller(MainCropsBuyingSellingTrafficLightController::class)->group(function () {
            Route::get('main-crops-buying-selling-traffic-light', 'index');
            Route::post('main-crops-buying-selling-traffic-light', 'store');
            Route::put('main-crops-buying-selling-traffic-light/{id}', 'update');
            Route::put('main-crops-buying-selling-traffic-light/{id}/status', 'changeStatus');
            Route::delete('main-crops-buying-selling-traffic-light/{id}', 'destroy');
        });

        // Market Data Transfer (Exportar/Importar datos de mercado entre entornos)
        Route::get('export-market-data', [MarketDataTransferController::class, 'export']);
        Route::post('import-market-data', [MarketDataTransferController::class, 'import']);

        // Business Indicator Data Transfer (Exportar/Importar datos de indicadores comerciales entre entornos)
        Route::get('export-business-indicator-data', [BusinessIndicatorDataTransferController::class, 'export']);
        Route::post('import-business-indicator-data', [BusinessIndicatorDataTransferController::class, 'import']);

        // Market General Controls (Control General de Mercado)
        Route::controller(MarketGeneralControlController::class)->group(function () {
            Route::get('market-general-controls', 'index');
            Route::get('market-general-controls/{id}', 'show');
            Route::post('market-general-controls', 'store');
            Route::put('market-general-controls/{id}', 'update');
            Route::put('market-general-controls/{id}/data', 'updateData');
            Route::put('market-general-controls/{id}/status', 'changeStatus');
            Route::delete('market-general-controls/{id}', 'destroy');
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

        // Unit of Measures (Unidades de medida)
        Route::controller(UnitOfMeasureController::class)->group(function () {
            Route::post('unit-of-measures', 'store');
            Route::put('unit-of-measures/{id}', 'update');
            Route::put('unit-of-measures/{id}/status', 'changeStatus');
            Route::delete('unit-of-measures/{id}', 'destroy');
        });

        // Economic Variables (Variables económicas)
        Route::controller(EconomicVariableController::class)->group(function () {
            Route::post('economic-variables', 'store');
            Route::put('economic-variables/{id}', 'update');
            Route::put('economic-variables/{id}/status', 'changeStatus');
            Route::delete('economic-variables/{id}', 'destroy');
        });
    }
);
// Unit of Measures - Public route
Route::get('admin/unit-of-measures', [UnitOfMeasureController::class, 'index']);

// Economic Variables - Public route
Route::get('admin/economic-variables', [EconomicVariableController::class, 'index']);

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