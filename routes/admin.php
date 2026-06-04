<?php

use App\Http\Controllers\AdminRoleController;
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
use App\Http\Controllers\ReportImageController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserCompanyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EconomicVariableController;
use App\Http\Controllers\UnitOfMeasureController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::post('admin/auth', [AuthController::class, 'auth_login_admin']);

Route::prefix('admin')
    ->middleware(['admin', 'check_permissions_hash'])
    ->group(function () {

        // -------------------------------------------------------
        // Módulo: Administración > Módulos
        // -------------------------------------------------------
        Route::middleware(['check_module:admin_modulos'])
            ->controller(AdminRoleController::class)
            ->group(function () {
                Route::get('modules', 'modules');
            });

        // -------------------------------------------------------
        // Módulo: Administración > Roles
        // -------------------------------------------------------
        Route::middleware(['check_module:admin_roles'])
            ->controller(AdminRoleController::class)
            ->group(function () {
                Route::get('roles', 'index');
                Route::get('roles/{id}', 'show');
                Route::post('roles', 'store');
                Route::put('roles/{id}', 'update');
            });

        // -------------------------------------------------------
        // Módulo: Administración > Asignación de rol
        // -------------------------------------------------------
        Route::middleware(['check_module:asignacion_rol'])
            ->post('users/{id}/role', [UserController::class, 'assignRole']);

        // -------------------------------------------------------
        // Módulo: Usuarios
        // -------------------------------------------------------
        Route::middleware(['check_module:usuarios'])
            ->controller(UserController::class)
            ->group(function () {
                Route::get('users', 'index');
                Route::get('users/export', 'export');
                Route::get('users/status', 'get_user_status');
                Route::get('users/with-referrals', 'getUsersWithReferrals');
                Route::get('users/send-welcome-email/{id}', 'send_welcome_email');
                Route::get('users/{id}', 'show');
                Route::get('users/{id}/subscription-history', 'subscriptionHistory');
                Route::post('users/create', 'store');
                Route::post('users/edit/profile_picture', 'profilePictureAdmin');
                Route::put('users/edit/{id}', 'update');
                Route::delete('users/delete-by-id/{id}', 'destroy');
                Route::post('users/change-status/{id}', 'changeStatus');
            });

        // -------------------------------------------------------
        // Módulo: Planes empresa (incluye historial de suscripciones)
        // -------------------------------------------------------
        Route::middleware(['check_module:planes_empresa'])
            ->group(function () {
                Route::controller(SubscriptionController::class)->group(function () {
                    Route::get('subscription/payment/history/{id}', 'subscription_plan_by_id');
                });

                Route::controller(CompanyPlanController::class)->group(function () {
                    Route::get('company-plans', 'index');
                    Route::get('company-plans/status', 'companyPlanStatus');
                    Route::get('company-plans/{id}', 'show');
                    Route::post('company-plans/status/{id}', 'changeStatus');
                    Route::post('company-plans', 'store');
                    Route::put('company-plans/{id}', 'update');
                });
            });

        // -------------------------------------------------------
        // Módulo: Gestión de empresas
        // -------------------------------------------------------
        Route::middleware(['check_module:gestion_empresas'])
            ->group(function () {
                Route::controller(CompanyController::class)->group(function () {
                    Route::get('company', 'index');
                    Route::get('company/with-active-plans', 'companiesWithActivePlans');
                    Route::get('company/status', 'companyStatus');
                    Route::get('company/all-permissions', 'allPermissions');
                    Route::get('company/{id}', 'show');
                    Route::post('company', 'store');
                    Route::post('company/{id}', 'update');
                    Route::post('company/logo/{id}', 'updateLogo');
                });

                Route::controller(UserCompanyController::class)->group(function () {
                    Route::post('user-company/add-main-admin', 'addMainAdminCompanyPlan');
                });
            });

        // -------------------------------------------------------
        // Módulo: Gestión de publicidades
        // -------------------------------------------------------
        Route::middleware(['check_module:gestion_publicidades'])
            ->group(function () {
                Route::controller(CompanyPlanPublicitiesReportController::class)->group(function () {
                    Route::post('company-plan-publicities-reports', 'store');
                    Route::put('company-plan-publicities-reports/{id}', 'update');
                });

                Route::controller(CompaniesAdvertisingController::class)->group(function () {
                    Route::post('advertising-companies', 'store');
                    Route::post('advertising-companies/{id}', 'update');
                    Route::put('advertising-companies/status/{id}', 'changeStatus');
                });
            });

        // -------------------------------------------------------
        // Módulo: Espacios publicitarios
        // -------------------------------------------------------
        Route::middleware(['check_module:espacios_publicitarios'])
            ->controller(AdvertisingSpaceController::class)
            ->group(function () {
                Route::post('advertising-space', 'store');
                Route::put('advertising-space/{id}', 'update');
                Route::put('advertising-space/status/{id}', 'changeStatus');
            });

        // -------------------------------------------------------
        // Mercado > Noticias
        // -------------------------------------------------------
        Route::middleware(['check_module:mercado_news'])
            ->controller(NewsController::class)
            ->group(function () {
                Route::get('news', 'index');
                Route::get('news/gallery', 'gallery');
                Route::post('news', 'store');
                Route::put('news/{id}', 'update');
                Route::post('news/image/{id}', 'updateImage');
                Route::delete('news/image/{id}', 'deleteImage');
                Route::put('news/{id}/status', 'changeStatus');
                Route::delete('news/{id}', 'destroy');
            });

        // -------------------------------------------------------
        // Mercado > Índice Arrendamiento
        // -------------------------------------------------------
        Route::middleware(['check_module:mercado_mag_lease_index'])
            ->controller(MagLeaseIndexController::class)
            ->group(function () {
                Route::get('mag-lease-index', 'index');
                Route::post('mag-lease-index', 'store');
                Route::put('mag-lease-index/{id}', 'update');
                Route::put('mag-lease-index/{id}/status', 'changeStatus');
                Route::delete('mag-lease-index/{id}', 'destroy');
            });

        // -------------------------------------------------------
        // Mercado > Índice Novillo
        // -------------------------------------------------------
        Route::middleware(['check_module:mercado_mag_steer_index'])
            ->controller(MagSteerIndexController::class)
            ->group(function () {
                Route::get('mag-steer-index', 'index');
                Route::post('mag-steer-index', 'store');
                Route::put('mag-steer-index/{id}', 'update');
                Route::put('mag-steer-index/{id}/status', 'changeStatus');
                Route::delete('mag-steer-index/{id}', 'destroy');
            });

        // -------------------------------------------------------
        // Mercado > Cultivos principales
        // -------------------------------------------------------
        Route::middleware(['check_module:mercado_major_crops'])
            ->controller(MajorCropController::class)
            ->group(function () {
                Route::get('major-crops', 'index');
                Route::post('major-crops', 'store');
                Route::put('major-crops/{id}', 'update');
                Route::put('major-crops/{id}/status', 'changeStatus');
                Route::delete('major-crops/{id}', 'destroy');
            });

        // -------------------------------------------------------
        // Mercado > Insights
        // -------------------------------------------------------
        Route::middleware(['check_module:mercado_insights'])
            ->controller(InsightController::class)
            ->group(function () {
                Route::get('insights', 'index');
                Route::post('insights', 'store');
                Route::put('insights/{id}', 'update');
                Route::put('insights/{id}/status', 'changeStatus');
                Route::delete('insights/{id}', 'destroy');
            });

        // -------------------------------------------------------
        // Mercado > Lluvias por provincia
        // -------------------------------------------------------
        Route::middleware(['check_module:mercado_rainfall_records'])
            ->controller(RainfallRecordController::class)
            ->group(function () {
                Route::get('rainfall-records', 'index');
                Route::post('rainfall-records', 'store');
                Route::put('rainfall-records/{id}', 'update');
                Route::put('rainfall-records/{id}/status', 'changeStatus');
                Route::delete('rainfall-records/{id}', 'destroy');
            });

        // -------------------------------------------------------
        // Mercado > Precios granos
        // -------------------------------------------------------
        Route::middleware(['check_module:mercado_main_grain_prices'])
            ->controller(MainGrainPriceController::class)
            ->group(function () {
                Route::get('main-grain-prices', 'index');
                Route::post('main-grain-prices', 'store');
                Route::put('main-grain-prices/{id}', 'update');
                Route::put('main-grain-prices/{id}/status', 'changeStatus');
                Route::delete('main-grain-prices/{id}', 'destroy');
            });

        // -------------------------------------------------------
        // Mercado > Precios insumos productor
        // -------------------------------------------------------
        Route::middleware(['check_module:mercado_price_active_ingredients'])
            ->controller(PriceMainActiveIngredientsProducerController::class)
            ->group(function () {
                Route::get('price-main-active-ingredients-producers', 'index');
                Route::post('price-main-active-ingredients-producers', 'store');
                Route::put('price-main-active-ingredients-producers/{id}', 'update');
                Route::put('price-main-active-ingredients-producers/{id}/status', 'changeStatus');
                Route::delete('price-main-active-ingredients-producers/{id}', 'destroy');
            });

        // -------------------------------------------------------
        // Mercado > Precios por segmento productor
        // -------------------------------------------------------
        Route::middleware(['check_module:mercado_producer_segment_prices'])
            ->controller(ProducerSegmentPriceController::class)
            ->group(function () {
                Route::get('producer-segment-prices', 'index');
                Route::post('producer-segment-prices', 'store');
                Route::put('producer-segment-prices/{id}', 'update');
                Route::put('producer-segment-prices/{id}/status', 'changeStatus');
                Route::delete('producer-segment-prices/{id}', 'destroy');
            });

        // -------------------------------------------------------
        // Mercado > Control general y datos (export/import)
        // -------------------------------------------------------
        Route::middleware(['check_module:mercado_general_control'])
            ->group(function () {
                Route::controller(MarketGeneralControlController::class)->group(function () {
                    Route::get('market-general-controls', 'index');
                    Route::post('market-general-controls', 'store');
                    Route::put('market-general-controls/replicate-additional-info', 'replicateAdditionalInfo');
                    Route::get('market-general-controls/{id}', 'show');
                    Route::put('market-general-controls/{id}', 'update');
                    Route::put('market-general-controls/{id}/data', 'updateData');
                    Route::put('market-general-controls/{id}/status', 'changeStatus');
                    Route::delete('market-general-controls/{id}', 'destroy');
                });

                Route::get('export-market-data', [MarketDataTransferController::class, 'export']);
                Route::post('import-market-data', [MarketDataTransferController::class, 'import']);
            });

        // -------------------------------------------------------
        // Indicadores > PIT
        // -------------------------------------------------------
        Route::middleware(['check_module:indicadores_pit'])
            ->controller(PitIndicatorController::class)
            ->group(function () {
                Route::get('pit-indicators', 'index');
                Route::post('pit-indicators', 'store');
                Route::put('pit-indicators/{id}', 'update');
                Route::put('pit-indicators/{id}/status', 'changeStatus');
                Route::delete('pit-indicators/{id}', 'destroy');
            });

        // -------------------------------------------------------
        // Indicadores > Márgenes brutos
        // -------------------------------------------------------
        Route::middleware(['check_module:indicadores_gross_margins'])
            ->controller(GrossMarginController::class)
            ->group(function () {
                Route::get('gross-margins', 'index');
                Route::post('gross-margins', 'store');
                Route::put('gross-margins/{id}', 'update');
                Route::put('gross-margins/{id}/status', 'changeStatus');
                Route::delete('gross-margins/{id}', 'destroy');
            });

        // -------------------------------------------------------
        // Indicadores > Tendencia márgenes
        // -------------------------------------------------------
        Route::middleware(['check_module:indicadores_gross_margins_trend'])
            ->controller(GrossMarginsTrendController::class)
            ->group(function () {
                Route::get('gross-margins-trend', 'index');
                Route::post('gross-margins-trend', 'store');
                Route::put('gross-margins-trend/{id}', 'update');
                Route::put('gross-margins-trend/{id}/status', 'changeStatus');
                Route::delete('gross-margins-trend/{id}', 'destroy');
                Route::delete('gross-margins-trend/duplicates/delete', 'deleteDuplicates');
            });

        // -------------------------------------------------------
        // Indicadores > Relación insumo-producto ganadera
        // -------------------------------------------------------
        Route::middleware(['check_module:indicadores_livestock'])
            ->controller(LivestockInputOutputRatioController::class)
            ->group(function () {
                Route::get('livestock-input-output-ratios', 'index');
                Route::post('livestock-input-output-ratios', 'store');
                Route::put('livestock-input-output-ratios/{id}', 'update');
                Route::put('livestock-input-output-ratios/{id}/status', 'changeStatus');
                Route::delete('livestock-input-output-ratios/{id}', 'destroy');
                Route::delete('livestock-input-output-ratios/duplicates/delete', 'deleteDuplicates');
            });

        // -------------------------------------------------------
        // Indicadores > Relación insumo-producto agrícola
        // -------------------------------------------------------
        Route::middleware(['check_module:indicadores_agricultural'])
            ->controller(AgriculturalInputOutputRelationshipController::class)
            ->group(function () {
                Route::get('agricultural-input-output-relationships', 'index');
                Route::post('agricultural-input-output-relationships', 'store');
                Route::put('agricultural-input-output-relationships/{id}', 'update');
                Route::put('agricultural-input-output-relationships/{id}/status', 'changeStatus');
                Route::delete('agricultural-input-output-relationships/{id}', 'destroy');
                Route::delete('agricultural-input-output-relationships/duplicates/delete', 'deleteDuplicates');
            });

        // -------------------------------------------------------
        // Indicadores > Precios productos
        // -------------------------------------------------------
        Route::middleware(['check_module:indicadores_product_prices'])
            ->controller(ProductPriceController::class)
            ->group(function () {
                Route::get('product-prices', 'index');
                Route::post('product-prices', 'store');
                Route::put('product-prices/{id}', 'update');
                Route::put('product-prices/{id}/status', 'changeStatus');
                Route::delete('product-prices/{id}', 'destroy');
            });

        // -------------------------------------------------------
        // Indicadores > Precios cosecha
        // -------------------------------------------------------
        Route::middleware(['check_module:indicadores_harvest_prices'])
            ->controller(HarvestPricesController::class)
            ->group(function () {
                Route::get('harvest-prices', 'index');
                Route::post('harvest-prices', 'store');
                Route::put('harvest-prices/{id}', 'update');
                Route::put('harvest-prices/{id}/status', 'changeStatus');
                Route::delete('harvest-prices/{id}', 'destroy');
                Route::delete('harvest-prices/duplicates/delete', 'deleteDuplicates');
            });

        // -------------------------------------------------------
        // Indicadores > Semáforo compra/venta cultivos principales
        // -------------------------------------------------------
        Route::middleware(['check_module:indicadores_traffic_light'])
            ->controller(MainCropsBuyingSellingTrafficLightController::class)
            ->group(function () {
                Route::get('main-crops-buying-selling-traffic-light', 'index');
                Route::post('main-crops-buying-selling-traffic-light', 'store');
                Route::put('main-crops-buying-selling-traffic-light/{id}', 'update');
                Route::put('main-crops-buying-selling-traffic-light/{id}/status', 'changeStatus');
                Route::delete('main-crops-buying-selling-traffic-light/{id}', 'destroy');
            });

        // -------------------------------------------------------
        // Indicadores > Business indicator controls y datos (export/import)
        // -------------------------------------------------------
        Route::middleware(['check_module:indicadores_business_controls'])
            ->group(function () {
                Route::controller(BusinessIndicatorControlController::class)->group(function () {
                    Route::get('business-indicator-controls', 'index');
                    Route::post('business-indicator-controls', 'store');
                    Route::put('business-indicator-controls/replicate-additional-info', 'replicateAdditionalInfo');
                    Route::get('business-indicator-controls/{id}', 'show');
                    Route::put('business-indicator-controls/{id}', 'update');
                    Route::put('business-indicator-controls/{id}/data', 'updateData');
                    Route::put('business-indicator-controls/{id}/status', 'changeStatus');
                    Route::delete('business-indicator-controls/{id}', 'destroy');
                });

                Route::get('export-business-indicator-data', [BusinessIndicatorDataTransferController::class, 'export']);
                Route::post('import-business-indicator-data', [BusinessIndicatorDataTransferController::class, 'import']);
            });

        // -------------------------------------------------------
        // Módulo: Configuración > Iconos
        // -------------------------------------------------------
        Route::middleware(['check_module:config_iconos'])
            ->controller(IconController::class)
            ->group(function () {
                Route::get('icons', 'index');
                Route::get('icons/{id}', 'show');
                Route::post('icons', 'store');
                Route::post('icons/{id}', 'update');
                Route::delete('icons/{id}', 'destroy');
            });

        // -------------------------------------------------------
        // Módulo: Configuración > Imágenes
        // -------------------------------------------------------
        Route::middleware(['check_module:config_imagenes'])
            ->group(function () {
                Route::controller(ImageController::class)->group(function () {
                    Route::post('images', 'store');
                    Route::post('images/{id}', 'update');
                    Route::delete('images/{id}', 'destroy');
                });

                Route::controller(ReportImageController::class)->group(function () {
                    Route::post('report-images', 'store');
                    Route::post('report-images/{fileName}', 'update');
                    Route::delete('report-images/{fileName}', 'destroy');
                });
            });

        // -------------------------------------------------------
        // Módulo: Configuración > FAQs
        // -------------------------------------------------------
        Route::middleware(['check_module:config_faqs'])
            ->controller(FaqController::class)
            ->group(function () {
                Route::post('faqs', 'store');
                Route::put('faqs/{id}', 'update');
                Route::put('faqs/{id}/status', 'changeStatus');
                Route::delete('faqs/{id}', 'destroy');
            });

        // -------------------------------------------------------
        // Módulo: Configuración > Regiones
        // -------------------------------------------------------
        Route::middleware(['check_module:config_regiones'])
            ->controller(RegionController::class)
            ->group(function () {
                Route::post('regions', 'store');
                Route::put('regions/{id}', 'update');
                Route::put('regions/{id}/status', 'changeStatus');
                Route::delete('regions/{id}', 'destroy');
            });

        // -------------------------------------------------------
        // Módulo: Configuración > Perfiles
        // -------------------------------------------------------
        Route::middleware(['check_module:config_perfiles'])
            ->controller(UserProfileController::class)
            ->group(function () {
                Route::post('user-profiles', 'store');
                Route::put('user-profiles/{id}', 'update');
                Route::put('user-profiles/{id}/status', 'changeStatus');
                Route::delete('user-profiles/{id}', 'destroy');
            });

        // -------------------------------------------------------
        // Módulo: Configuración > Planes
        // -------------------------------------------------------
        Route::middleware(['check_module:config_planes'])
            ->controller(PlanController::class)
            ->group(function () {
                Route::put('plans/{id}', 'update');
            });

        // -------------------------------------------------------
        // Módulo: Configuración > Clasificaciones
        // -------------------------------------------------------
        Route::middleware(['check_module:config_clasificaciones'])
            ->controller(ClassificationController::class)
            ->group(function () {
                Route::post('classifications', 'store');
                Route::put('classifications/{id}', 'update');
                Route::put('classifications/{id}/status', 'changeStatus');
                Route::delete('classifications/{id}', 'destroy');
            });

        // -------------------------------------------------------
        // Módulo: Configuración > Productos
        // -------------------------------------------------------
        Route::middleware(['check_module:config_productos'])
            ->controller(ProductController::class)
            ->group(function () {
                Route::post('products', 'store');
                Route::put('products/{id}', 'update');
                Route::put('products/{id}/status', 'changeStatus');
                Route::delete('products/{id}', 'destroy');
            });

        // -------------------------------------------------------
        // Módulo: Configuración > Cultivos
        // -------------------------------------------------------
        Route::middleware(['check_module:config_cultivos'])
            ->group(function () {
                Route::controller(CropController::class)->group(function () {
                    Route::get('crops', 'index');
                    Route::post('crops', 'store');
                    Route::put('crops/{id}', 'update');
                    Route::delete('crops/{id}', 'destroy');
                });

                Route::controller(ActiveIngredientController::class)->group(function () {
                    Route::get('active-ingredients', 'index');
                    Route::post('active-ingredients', 'store');
                    Route::put('active-ingredients/{id}', 'update');
                    Route::delete('active-ingredients/{id}', 'destroy');
                });
            });

        // -------------------------------------------------------
        // Módulo: Configuración > Unidades
        // -------------------------------------------------------
        Route::middleware(['check_module:config_unidades'])
            ->controller(UnitOfMeasureController::class)
            ->group(function () {
                Route::post('unit-of-measures', 'store');
                Route::put('unit-of-measures/{id}', 'update');
                Route::put('unit-of-measures/{id}/status', 'changeStatus');
                Route::delete('unit-of-measures/{id}', 'destroy');
            });

        // -------------------------------------------------------
        // Módulo: Configuración > Variables económicas
        // -------------------------------------------------------
        Route::middleware(['check_module:config_variables'])
            ->controller(EconomicVariableController::class)
            ->group(function () {
                Route::post('economic-variables', 'store');
                Route::put('economic-variables/{id}', 'update');
                Route::put('economic-variables/{id}/status', 'changeStatus');
                Route::delete('economic-variables/{id}', 'destroy');
            });

        // -------------------------------------------------------
        // Sin restricción de módulo — accesibles a todos los roles admin
        // (auditoría, estados, eventos, reportes de estado)
        // -------------------------------------------------------
        Route::controller(AudithController::class)->group(function () {
            Route::get('audiths', 'index');
        });

        Route::controller(StatusController::class)->group(function () {
            Route::get('status', 'index');
        });

        Route::controller(EventController::class)->group(function () {
            Route::get('events', 'index');
            Route::get('events/{id}', 'show');
            Route::post('events', 'store');
            Route::put('events/{id}', 'update');
            Route::delete('events/{id}', 'destroy');
        });

        Route::post('events/import-users', [EventImportController::class, 'import']);

        Route::controller(ReportController::class)->group(function () {
            Route::get('status-report', 'statusReport');
        });
    });

// -------------------------------------------------------
// Rutas públicas (sin middleware admin)
// -------------------------------------------------------

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

// reports images - Public route
Route::get('admin/report-images', [ReportImageController::class, 'index']);
