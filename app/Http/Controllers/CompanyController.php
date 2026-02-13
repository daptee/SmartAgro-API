<?php

namespace App\Http\Controllers;

use App\Models\AgriculturalInputOutputRelationship;
use App\Models\Audith;
use App\Models\Company;
use App\Models\CompanyApiUsages;
use App\Models\GrossMargin;
use App\Models\GrossMarginsTrend;
use App\Models\HarvestPrices;
use App\Models\Insight;
use App\Models\LivestockInputOutputRatio;
use App\Models\MagLeaseIndex;
use App\Models\MagSteerIndex;
use App\Models\MainCropsBuyingSellingTrafficLight;
use App\Models\MainGrainPrice;
use App\Models\MajorCrop;
use App\Models\MarketGeneralControl;
use App\Models\News;
use App\Models\PitIndicator;
use App\Models\PriceMainActiveIngredientsProducer;
use App\Models\ProducerSegmentPrice;
use App\Models\ProductPrice;
use App\Models\RainfallRecordProvince;
use App\Models\Status;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function store(Request $request)
    {
        $message = "Error al crear empresa";
        $action = "Crear empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $request->validate([
                'company_name' => 'required|unique:companies,company_name',
                'cuit' => 'required|unique:companies,cuit',
                'email' => 'required|email|unique:companies,email',
                'main_color' => 'nullable|string|max:255',
                'secondary_color' => 'nullable|string|max:255',
                'id_locality' => 'nullable|exists:localities,id',
                'id_company_category' => 'nullable|exists:company_categories,id',
                'range_number_of_employees' => 'nullable|string|max:255',
                'website' => 'nullable|string|max:255',
                'status' => 'nullable|integer|in:1,2',
                // Nuevos campos opcionales
                'generate_api_key' => 'nullable|boolean',
                'api_permissions' => 'nullable|array',
                'api_permissions.*' => 'array',
            ]);

            $data = Company::create([
                'company_name' => $request->company_name,
                'cuit' => $request->cuit,
                'email' => $request->email,
                'main_color' => $request->main_color,
                'secondary_color' => $request->secondary_color,
                'id_locality' => $request->id_locality,
                'id_company_category' => $request->id_company_category,
                'range_number_of_employees' => $request->range_number_of_employees,
                'website' => $request->website,
                'status_id' => $request->status ?? 1,
            ]);

            // Si el request indica generar nueva APIKEY
            if ($request->boolean('generate_api_key')) {
                $data->api_key = bin2hex(random_bytes(32));
            }

            // Si se envían permisos
            if ($request->has('api_permissions')) {
                $data->api_permissions = $request->api_permissions;
            }

            $data->save();

            $data->load([
                'locality.province',
                'category',
                'status'
            ]);

            Audith::new($id_user, $action, $request->all(), 201, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"), 201);
    }

    public function update(Request $request, $id)
    {
        $message = "Error al actualizar empresa";
        $action = "Actualizar empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $company = Company::findOrFail($id);

            if ($request->has('api_permissions') && is_string($request->api_permissions)) {
                $decoded = json_decode($request->api_permissions, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $request->merge(['api_permissions' => $decoded]);
                } else {
                    return response()->json([
                        'message' => 'Error al actualizar empresa',
                        'error' => 'El campo api_permissions debe ser un JSON válido.',
                    ], 422);
                }
            }

            $request->validate([
                'company_name' => 'required|unique:companies,company_name,' . $company->id,
                'cuit' => 'required|unique:companies,cuit,' . $company->id,
                'email' => 'required|email|unique:companies,email,' . $company->id,
                'main_color' => 'nullable|string|max:255',
                'secondary_color' => 'nullable|string|max:255',
                'id_locality' => 'nullable|exists:localities,id',
                'id_company_category' => 'nullable|exists:company_categories,id',
                'range_number_of_employees' => 'nullable|string|max:255',
                'website' => 'nullable|string|max:255',
                'status' => 'nullable|integer|in:1,2',
                // Nuevos campos opcionales
                'generate_api_key' => 'nullable|boolean',
                'api_permissions' => 'nullable|array',
                'api_permissions.*' => 'array',
            ]);

            // Actualizar campos
            $company->update([
                'company_name' => $request->company_name,
                'cuit' => $request->cuit,
                'email' => $request->email,
                'main_color' => $request->main_color,
                'secondary_color' => $request->secondary_color,
                'id_locality' => $request->id_locality,
                'id_company_category' => $request->id_company_category,
                'range_number_of_employees' => $request->range_number_of_employees,
                'website' => $request->website,
                'status_id' => $request->status ?? 1,
            ]);

            // Si el request indica generar nueva APIKEY
            if ($request->boolean('generate_api_key')) {
                $company->api_key = bin2hex(random_bytes(32));
            }

            // Si se envían permisos
            if ($request->has('api_permissions')) {
                $company->api_permissions = $request->api_permissions;
            }

            $company->save();

            $company->load([
                'locality.province',
                'category',
                'status'
            ]);

            $data = $company;
            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function update_logo(Request $request, $id)
    {
        $message = "Error al actualizar logo de la empresa";
        $action = "Actualizar logo de la empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $company = Company::findOrFail($id);

            $request->validate([
                'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            ]);

            if ($request->hasFile('logo')) {
                // Eliminar imagen anterior
                if ($company->logo && file_exists(public_path($company->logo))) {
                    @unlink(public_path($company->logo));
                }

                // Guardar nueva imagen
                $logo = $request->file('logo');
                $pathToSave = 'storage/company/logos';
                $fullPath = public_path($pathToSave);

                // Intentar crear el directorio si no existe
                if (!is_dir($fullPath)) {
                    @mkdir($fullPath, 0755, true);
                }

                $logoName = time() . '_logo_' . $logo->getClientOriginalName();

                try {
                    $logo->move($fullPath, $logoName);
                    $company->logo = '/' . $pathToSave . '/' . $logoName;
                } catch (\Exception $e) {
                    throw new \RuntimeException('Error al guardar el logo: ' . $e->getMessage());
                }
            } elseif ($request->logo === null) {
                // Si se manda explícitamente null
                if ($company->logo && file_exists(public_path($company->logo))) {
                    @unlink(public_path($company->logo));
                }
                $company->logo = null;
            }
            // Si se manda string se ignora el campo logo

            $company->save();

            $company->load([
                'locality.province',
                'category',
                'status'
            ]);

            $data = $company;
            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function index(Request $request)
    {
        $message = "Error al obtener las empresas";
        $action = "Listado de empresas";
        $data = null;
        $meta = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $perPage = $request->query('per_page'); // ahora sin valor por defecto
            $page = $request->query('page', 1);
            $province = $request->query('province');
            $localy = $request->query('localy');
            $category = $request->query('category');
            $status = $request->query('status');
            $search = $request->query('search');

            // Iniciar consulta con relaciones
            $query = Company::with(['category', 'locality.province', 'status']);

            // Filtros
            if (!is_null($province)) {
                $query->whereHas('locality.province', function ($q) use ($province) {
                    $q->where('id', $province);
                });
            }

            if (!is_null($localy)) {
                $query->whereHas('locality', function ($q) use ($localy) {
                    $q->where('id', $localy);
                });
            }

            if (!is_null($category)) {
                $query->whereHas('category', function ($q) use ($category) {
                    $q->where('id', $category);
                });
            }

            if (!is_null($status)) {
                $query->whereHas('status', function ($q) use ($status) {
                    $q->where('id', $status);
                });
            }

            // Buscador
            if (!is_null($search)) {
                $query->where('company_name', 'like', '%' . $search . '%');
            }

            // Orden por defecto (alfabético)
            $query->orderBy('company_name', 'asc');

            // Si no se pasa per_page => devolver todo
            if (is_null($perPage)) {
                $companies = $query->get();
                $data =  $companies;
            } else {
                $companies = $query->paginate($perPage, ['*'], 'page', $page);
                $data = $companies->items();
                $meta = [
                        'page' => $companies->currentPage(),
                        'per_page' => $companies->perPage(),
                        'total' => $companies->total(),
                        'last_page' => $companies->lastPage(),
                ];
            }

            Audith::new($id_user, $action, $request->all(), 200, compact("action", "data", "meta"));
            
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data", "meta"));
    }

    public function companyStatus(Request $request)
    {
        $message = "Error al obtener los estados de las empresas";
        $action = "Listado de estados de empresas";
        $data = null;
        $id_user = Auth::user()->id ?? null;
        try {
            $data = Status::get();

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));

    }

    public function show(Request $request, $id)
    {
        $message = "Error al obtener la empresa";
        $action = "Empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;
        try {
            $company = Company::with(['category', 'locality.province', 'status'])->findOrFail($id);
            $data = $company;
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function companiesWithActivePlans(Request $request)
    {
        $message = "Error al obtener compañías con planes activos";
        $action = "Listado de compañías con planes activos";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $companies = Company::whereHas('plan', function ($q) {
                $q->where('status_id', 1); // Solo planes activos
            })
                ->with([
                    'plan' => function ($q) {
                        $q->where('status_id', 1)
                            ->with(['status']); // Incluye datos del status si quieres
                    },
                    'category',
                    'locality',
                    'status'
                ])
                ->get();

            $data = $companies;

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    private array $models = [
        News::class => 'Noticias',
        Insight::class => 'Perspectivas',
        MagLeaseIndex::class => 'Índice de arrendamiento magnético',
        MagSteerIndex::class => 'Índice de novillo magnético',
        MajorCrop::class => 'Perspectivas de los principales cultivos',
        MainGrainPrice::class => 'Precios de los principales granos',
        PriceMainActiveIngredientsProducer::class => 'Precios de los principales ingredientes activos para productores',
        ProducerSegmentPrice::class => 'Precios por segmento para productores',
        RainfallRecordProvince::class => 'Registros de precipitaciones por provincia',
        PitIndicator::class => 'Indicadores PIT',
        LivestockInputOutputRatio::class => 'Relación insumo/producto ganadero',
        AgriculturalInputOutputRelationship::class => 'Relación insumo/producto agrícola',
        GrossMarginsTrend::class => 'Tendencia de márgenes brutos',
        HarvestPrices::class => 'Precios de cosecha',
        ProductPrice::class => 'Precios de productos',
        GrossMargin::class => 'Márgenes brutos',
        MainCropsBuyingSellingTrafficLight::class => 'Semáforo de compra/venta de cultivos principales',
    ];

    public function allPermissions()
    {
        try {
            $permissions = collect($this->models)->map(function ($label, $modelClass) {
                return [
                    'name' => (new $modelClass)->getTable(), // nombre real de la tabla
                    'label' => $label,
                    'options' => ['enabled', 'months_back_limit', 'max_results'],
                ];
            })->values();

            return response()->json([
                'data' => $permissions,
                'message' => 'Listado de permisos disponibles para API Keys',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener permisos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function news(Request $request)
    {
        $message = "Error al obtener las noticias";
        $action = "Noticias";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $company = $request->get('_company');
            // GET news - Peticion que reciba una fecha desde y fecha hasta y retorne las noticias dentro de ese rango de fecha
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');

            $permissions = $company->api_permissions ?? [];
            if (!isset($permissions['news']['enabled']) || !$permissions['news']['enabled']) {
                Audith::new($id_user, $action, $request->all(), 403, "No tiene permisos para acceder a las noticias");
                return response(["message" => "No tiene permisos para acceder a las noticias"], 403);
            }

            $monthsLimit = $permissions['news']['months_back_limit'] ?? null;
            $maxResults = $permissions['news']['max_results'] ?? null;

            // Límite de meses permitido
            if ($monthsLimit !== null) {
                // ⚠️ corregido: NO se resta 1, así se incluyen los N meses completos
                $startAllowed = now()->startOfMonth()->subMonths($monthsLimit);
                $endAllowed = now()->endOfMonth();

                $parsedFrom = $dateFrom ? Carbon::parse($dateFrom) : null;
                $parsedTo = $dateTo ? Carbon::parse($dateTo) : null;

                // Si ambas fechas están completamente fuera del rango, no mostrar nada
                if (
                    ($parsedFrom && $parsedFrom->gt($endAllowed)) ||
                    ($parsedTo && $parsedTo->lt($startAllowed))
                ) {
                    return response()->json([
                        "data" => [],
                        "message" => "Las fechas solicitadas están fuera del límite permitido de meses."
                    ], 200);
                }

                // Ajustar fechas
                $dateFrom = $parsedFrom ? max($parsedFrom, $startAllowed)->toDateString() : $startAllowed->toDateString();
                $dateTo = $parsedTo ? min($parsedTo, $endAllowed)->toDateString() : $endAllowed->toDateString();
            }

            CompanyApiUsages::create([
                'id_company' => $company->id,
                'request_name' => $action,
                'params' => $request->all(),
            ]);

            // Obtener meses/años publicados en control general de mercado
            $publishedControls = MarketGeneralControl::where('status_id', 1)->get(['month', 'year']);
            $publishedPeriods = $publishedControls->map(fn($c) => $c->year . '-' . str_pad($c->month, 2, '0', STR_PAD_LEFT))->toArray();

            $data = News::query()
                ->whereRaw("DATE_FORMAT(date, '%Y-%m') IN (" . (count($publishedPeriods) > 0 ? implode(',', array_map(fn($p) => "'{$p}'", $publishedPeriods)) : "''" ) . ")")
                ->when($dateFrom, function ($query) use ($dateFrom) {
                    return $query->where('date', '>=', $dateFrom);
                })
                ->when($dateTo, function ($query) use ($dateTo) {
                    return $query->where('date', '<=', $dateTo);
                })
                ->when($maxResults, fn($q) => $q->limit($maxResults))
                ->orderBy('date', 'desc')
                ->get();

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function insights(Request $request)
    {
        $message = "Error al obtener las perspectivas";
        $action = "Perspectivas";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $company = $request->get('_company');
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');

            $permissions = $company->api_permissions ?? [];

            if (!isset($permissions['insights']['enabled']) || !$permissions['insights']['enabled']) {
                Audith::new($id_user, $action, $request->all(), 403, "No tiene permisos para acceder a las perspectivas");
                return response(["message" => "No tiene permisos para acceder a las perspectivas"], 403);
            }

            $monthsLimit = $permissions['insights']['months_back_limit'] ?? null;
            $maxResults = $permissions['insights']['max_results'] ?? null;

            // Límite de meses permitido
            if ($monthsLimit !== null) {
                // ⚠️ corregido: NO se resta 1, así se incluyen los N meses completos
                $startAllowed = now()->startOfMonth()->subMonths($monthsLimit);
                $endAllowed = now()->endOfMonth();

                $parsedFrom = $dateFrom ? Carbon::parse($dateFrom) : null;
                $parsedTo = $dateTo ? Carbon::parse($dateTo) : null;

                // Si ambas fechas están completamente fuera del rango, no mostrar nada
                if (
                    ($parsedFrom && $parsedFrom->gt($endAllowed)) ||
                    ($parsedTo && $parsedTo->lt($startAllowed))
                ) {
                    return response()->json([
                        "data" => [],
                        "message" => "Las fechas solicitadas están fuera del límite permitido de meses."
                    ], 200);
                }

                // Ajustar fechas
                $dateFrom = $parsedFrom ? max($parsedFrom, $startAllowed)->toDateString() : $startAllowed->toDateString();
                $dateTo = $parsedTo ? min($parsedTo, $endAllowed)->toDateString() : $endAllowed->toDateString();
            }

            CompanyApiUsages::create([
                'id_company' => $company->id,
                'request_name' => $action,
                'params' => $request->all(),
            ]);

            // Obtener meses/años publicados en control general de mercado
            $publishedControls = MarketGeneralControl::where('status_id', 1)->get(['month', 'year']);
            $publishedPeriods = $publishedControls->map(fn($c) => $c->year . '-' . str_pad($c->month, 2, '0', STR_PAD_LEFT))->toArray();

            $data = Insight::query()
                ->whereRaw("DATE_FORMAT(date, '%Y-%m') IN (" . (count($publishedPeriods) > 0 ? implode(',', array_map(fn($p) => "'{$p}'", $publishedPeriods)) : "''" ) . ")")
                ->when($dateFrom, function ($query) use ($dateFrom) {
                    return $query->where('date', '>=', $dateFrom);
                })
                ->when($dateTo, function ($query) use ($dateTo) {
                    return $query->where('date', '<=', $dateTo);
                })
                ->when($maxResults, fn($q) => $q->limit($maxResults))
                ->orderBy('date', 'desc')
                ->get();

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function mag_lease_index(Request $request)
    {
        $message = "Error al obtener los indice arrendamiento magnético";
        $action = "Indice arrendamiento mag";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $company = $request->get('_company');
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');

            $permissions = $company->api_permissions ?? [];
            if (!isset($permissions['mag lease index']['enabled']) || !$permissions['mag lease index']['enabled']) {
                Audith::new($id_user, $action, $request->all(), 403, "No tiene permisos para acceder a los indice arrendamiento magnético");
                return response(["message" => "No tiene permisos para acceder a los indice arrendamiento magnético"], 403);
            }

            $monthsLimit = $permissions['mag lease index']['months_back_limit'] ?? null;
            $maxResults = $permissions['mag lease index']['max_results'] ?? null;

            // Límite de meses permitido
            if ($monthsLimit !== null) {
                // ⚠️ corregido: NO se resta 1, así se incluyen los N meses completos
                $startAllowed = now()->startOfMonth()->subMonths($monthsLimit);
                $endAllowed = now()->endOfMonth();

                $parsedFrom = $dateFrom ? Carbon::parse($dateFrom) : null;
                $parsedTo = $dateTo ? Carbon::parse($dateTo) : null;

                // Si ambas fechas están completamente fuera del rango, no mostrar nada
                if (
                    ($parsedFrom && $parsedFrom->gt($endAllowed)) ||
                    ($parsedTo && $parsedTo->lt($startAllowed))
                ) {
                    return response()->json([
                        "data" => [],
                        "message" => "Las fechas solicitadas están fuera del límite permitido de meses."
                    ], 200);
                }

                // Ajustar fechas
                $dateFrom = $parsedFrom ? max($parsedFrom, $startAllowed)->toDateString() : $startAllowed->toDateString();
                $dateTo = $parsedTo ? min($parsedTo, $endAllowed)->toDateString() : $endAllowed->toDateString();
            }

            CompanyApiUsages::create([
                'id_company' => $company->id,
                'request_name' => $action,
                'params' => $request->all(),
            ]);

            // Obtener meses/años publicados en control general de mercado
            $publishedControls = MarketGeneralControl::where('status_id', 1)->get(['month', 'year']);
            $publishedPeriods = $publishedControls->map(fn($c) => $c->year . '-' . str_pad($c->month, 2, '0', STR_PAD_LEFT))->toArray();

            $data = MagLeaseIndex::query()
                ->whereRaw("DATE_FORMAT(date, '%Y-%m') IN (" . (count($publishedPeriods) > 0 ? implode(',', array_map(fn($p) => "'{$p}'", $publishedPeriods)) : "''" ) . ")")
                ->when($dateFrom, function ($query) use ($dateFrom) {
                    return $query->where('date', '>=', $dateFrom);
                })
                ->when($dateTo, function ($query) use ($dateTo) {
                    return $query->where('date', '<=', $dateTo);
                })
                ->when($maxResults, fn($q) => $q->limit($maxResults))
                ->orderBy('date', 'desc')
                ->get();

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function mag_steer_index(Request $request)
    {
        $message = "Error al obtener los indice novillo magnético";
        $action = "Indice novillo mag";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $company = $request->get('_company');
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');

            $permissions = $company->api_permissions ?? [];
            if (!isset($permissions['mag steer index']['enabled']) || !$permissions['mag steer index']['enabled']) {
                Audith::new($id_user, $action, $request->all(), 403, "No tiene permisos para acceder a los indice novillo magnético");
                return response(["message" => "No tiene permisos para acceder a los indice novillo magnético"], 403);
            }

            $monthsLimit = $permissions['mag steer index']['months_back_limit'] ?? null;
            $maxResults = $permissions['mag steer index']['max_results'] ?? null;

            // Límite de meses permitido
            if ($monthsLimit !== null) {
                // ⚠️ corregido: NO se resta 1, así se incluyen los N meses completos
                $startAllowed = now()->startOfMonth()->subMonths($monthsLimit);
                $endAllowed = now()->endOfMonth();

                $parsedFrom = $dateFrom ? Carbon::parse($dateFrom) : null;
                $parsedTo = $dateTo ? Carbon::parse($dateTo) : null;

                // Si ambas fechas están completamente fuera del rango, no mostrar nada
                if (
                    ($parsedFrom && $parsedFrom->gt($endAllowed)) ||
                    ($parsedTo && $parsedTo->lt($startAllowed))
                ) {
                    return response()->json([
                        "data" => [],
                        "message" => "Las fechas solicitadas están fuera del límite permitido de meses."
                    ], 200);
                }

                // Ajustar fechas
                $dateFrom = $parsedFrom ? max($parsedFrom, $startAllowed)->toDateString() : $startAllowed->toDateString();
                $dateTo = $parsedTo ? min($parsedTo, $endAllowed)->toDateString() : $endAllowed->toDateString();
            }

            CompanyApiUsages::create([
                'id_company' => $company->id,
                'request_name' => $action,
                'params' => $request->all(),
            ]);

            // Obtener meses/años publicados en control general de mercado
            $publishedControls = MarketGeneralControl::where('status_id', 1)->get(['month', 'year']);
            $publishedPeriods = $publishedControls->map(fn($c) => $c->year . '-' . str_pad($c->month, 2, '0', STR_PAD_LEFT))->toArray();

            $data = MagSteerIndex::query()
                ->whereRaw("DATE_FORMAT(date, '%Y-%m') IN (" . (count($publishedPeriods) > 0 ? implode(',', array_map(fn($p) => "'{$p}'", $publishedPeriods)) : "''" ) . ")")
                ->when($dateFrom, function ($query) use ($dateFrom) {
                    return $query->where('date', '>=', $dateFrom);
                })
                ->when($dateTo, function ($query) use ($dateTo) {
                    return $query->where('date', '<=', $dateTo);
                })
                ->when($maxResults, fn($q) => $q->limit($maxResults))
                ->orderBy('date', 'desc')
                ->get();

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function major_crops(Request $request)
    {
        $message = "Error al obtener las perspectivas de los principales cultivos";
        $action = "Perspectivas de los principales cultivos";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $company = $request->get('_company');
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');

            $permissions = $company->api_permissions ?? [];
            if (!isset($permissions['major crops']['enabled']) || !$permissions['major crops']['enabled']) {
                Audith::new($id_user, $action, $request->all(), 403, "No tiene permisos para acceder a las perspectivas de los principales cultivos");
                return response(["message" => "No tiene permisos para acceder a las perspectivas de los principales cultivos"], 403);
            }

            $monthsLimit = $permissions['major crops']['months_back_limit'] ?? null;
            $maxResults = $permissions['major crops']['max_results'] ?? null;

            // Límite de meses permitido
            if ($monthsLimit !== null) {
                // ⚠️ corregido: NO se resta 1, así se incluyen los N meses completos
                $startAllowed = now()->startOfMonth()->subMonths($monthsLimit);
                $endAllowed = now()->endOfMonth();

                $parsedFrom = $dateFrom ? Carbon::parse($dateFrom) : null;
                $parsedTo = $dateTo ? Carbon::parse($dateTo) : null;

                // Si ambas fechas están completamente fuera del rango, no mostrar nada
                if (
                    ($parsedFrom && $parsedFrom->gt($endAllowed)) ||
                    ($parsedTo && $parsedTo->lt($startAllowed))
                ) {
                    return response()->json([
                        "data" => [],
                        "message" => "Las fechas solicitadas están fuera del límite permitido de meses."
                    ], 200);
                }

                // Ajustar fechas
                $dateFrom = $parsedFrom ? max($parsedFrom, $startAllowed)->toDateString() : $startAllowed->toDateString();
                $dateTo = $parsedTo ? min($parsedTo, $endAllowed)->toDateString() : $endAllowed->toDateString();
            }

            CompanyApiUsages::create([
                'id_company' => $company->id,
                'request_name' => $action,
                'params' => $request->all(),
            ]);

            // Obtener meses/años publicados en control general de mercado
            $publishedControls = MarketGeneralControl::where('status_id', 1)->get(['month', 'year']);
            $publishedPeriods = $publishedControls->map(fn($c) => $c->year . '-' . str_pad($c->month, 2, '0', STR_PAD_LEFT))->toArray();

            $data = MajorCrop::query()
                ->whereRaw("DATE_FORMAT(date, '%Y-%m') IN (" . (count($publishedPeriods) > 0 ? implode(',', array_map(fn($p) => "'{$p}'", $publishedPeriods)) : "''" ) . ")")
                ->when($dateFrom, function ($query) use ($dateFrom) {
                    return $query->where('date', '>=', $dateFrom);
                })
                ->when($dateTo, function ($query) use ($dateTo) {
                    return $query->where('date', '<=', $dateTo);
                })
                ->when($maxResults, fn($q) => $q->limit($maxResults))
                ->orderBy('date', 'desc')
                ->get();

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function price_main_active_ingredients_producers(Request $request)
    {
        $message = "Error al obtener los precio de los principales ingredientes activos de los productores";
        $action = "Precio de los principales ingredientes activos de los productores";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $company = $request->get('_company');
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');

            $permissions = $company->api_permissions ?? [];
            if (!isset($permissions['price main active ingredients producers']['enabled']) || !$permissions['price main active ingredients producers']['enabled']) {
                Audith::new($id_user, $action, $request->all(), 403, "No tiene permisos para acceder a los precio de los principales ingredientes activos de los productores");
                return response(["message" => "No tiene permisos para acceder a los precio de los principales ingredientes activos de los productores"], 403);
            }

            $monthsLimit = $permissions['price main active ingredients producers']['months_back_limit'] ?? null;
            $maxResults = $permissions['price main active ingredients producers']['max_results'] ?? null;

            // Límite de meses permitido
            if ($monthsLimit !== null) {
                // ⚠️ corregido: NO se resta 1, así se incluyen los N meses completos
                $startAllowed = now()->startOfMonth()->subMonths($monthsLimit);
                $endAllowed = now()->endOfMonth();

                $parsedFrom = $dateFrom ? Carbon::parse($dateFrom) : null;
                $parsedTo = $dateTo ? Carbon::parse($dateTo) : null;

                // Si ambas fechas están completamente fuera del rango, no mostrar nada
                if (
                    ($parsedFrom && $parsedFrom->gt($endAllowed)) ||
                    ($parsedTo && $parsedTo->lt($startAllowed))
                ) {
                    return response()->json([
                        "data" => [],
                        "message" => "Las fechas solicitadas están fuera del límite permitido de meses."
                    ], 200);
                }

                // Ajustar fechas
                $dateFrom = $parsedFrom ? max($parsedFrom, $startAllowed)->toDateString() : $startAllowed->toDateString();
                $dateTo = $parsedTo ? min($parsedTo, $endAllowed)->toDateString() : $endAllowed->toDateString();
            }

            CompanyApiUsages::create([
                'id_company' => $company->id,
                'request_name' => $action,
                'params' => $request->all(),
            ]);

            // Obtener meses/años publicados en control general de mercado
            $publishedControls = MarketGeneralControl::where('status_id', 1)->get(['month', 'year']);
            $publishedPeriods = $publishedControls->map(fn($c) => $c->year . '-' . str_pad($c->month, 2, '0', STR_PAD_LEFT))->toArray();

            $data = PriceMainActiveIngredientsProducer::query()
                ->whereRaw("DATE_FORMAT(date, '%Y-%m') IN (" . (count($publishedPeriods) > 0 ? implode(',', array_map(fn($p) => "'{$p}'", $publishedPeriods)) : "''" ) . ")")
                ->when($dateFrom, function ($query) use ($dateFrom) {
                    return $query->where('date', '>=', $dateFrom);
                })
                ->when($dateTo, function ($query) use ($dateTo) {
                    return $query->where('date', '<=', $dateTo);
                })
                ->when($maxResults, fn($q) => $q->limit($maxResults))
                ->orderBy('date', 'desc')
                ->get();

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function producer_segment_prices(Request $request)
    {
        $message = "Error al obtener los precios por segmentos a productor";
        $action = "Precios por segmentos a productor";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $company = $request->get('_company');
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');

            $permissions = $company->api_permissions ?? [];
            if (!isset($permissions['producer segment prices']['enabled']) || !$permissions['producer segment prices']['enabled']) {
                Audith::new($id_user, $action, $request->all(), 403, "No tiene permisos para acceder a los precios por segmentos a productor");
                return response(["message" => "No tiene permisos para acceder a los precios por segmentos a productor"], 403);
            }

            $monthsLimit = $permissions['producer segment prices']['months_back_limit'] ?? null;
            $maxResults = $permissions['producer segment prices']['max_results'] ?? null;

            // Límite de meses permitido
            if ($monthsLimit !== null) {
                // ⚠️ corregido: NO se resta 1, así se incluyen los N meses completos
                $startAllowed = now()->startOfMonth()->subMonths($monthsLimit);
                $endAllowed = now()->endOfMonth();

                $parsedFrom = $dateFrom ? Carbon::parse($dateFrom) : null;
                $parsedTo = $dateTo ? Carbon::parse($dateTo) : null;

                // Si ambas fechas están completamente fuera del rango, no mostrar nada
                if (
                    ($parsedFrom && $parsedFrom->gt($endAllowed)) ||
                    ($parsedTo && $parsedTo->lt($startAllowed))
                ) {
                    return response()->json([
                        "data" => [],
                        "message" => "Las fechas solicitadas están fuera del límite permitido de meses."
                    ], 200);
                }

                // Ajustar fechas
                $dateFrom = $parsedFrom ? max($parsedFrom, $startAllowed)->toDateString() : $startAllowed->toDateString();
                $dateTo = $parsedTo ? min($parsedTo, $endAllowed)->toDateString() : $endAllowed->toDateString();
            }

            CompanyApiUsages::create([
                'id_company' => $company->id,
                'request_name' => $action,
                'params' => $request->all(),
            ]);

            // Obtener meses/años publicados en control general de mercado
            $publishedControls = MarketGeneralControl::where('status_id', 1)->get(['month', 'year']);
            $publishedPeriods = $publishedControls->map(fn($c) => $c->year . '-' . str_pad($c->month, 2, '0', STR_PAD_LEFT))->toArray();

            $data = ProducerSegmentPrice::query()
                ->whereRaw("DATE_FORMAT(date, '%Y-%m') IN (" . (count($publishedPeriods) > 0 ? implode(',', array_map(fn($p) => "'{$p}'", $publishedPeriods)) : "''" ) . ")")
                ->when($dateFrom, function ($query) use ($dateFrom) {
                    return $query->where('date', '>=', $dateFrom);
                })
                ->when($dateTo, function ($query) use ($dateTo) {
                    return $query->where('date', '<=', $dateTo);
                })
                ->when($maxResults, fn($q) => $q->limit($maxResults))
                ->orderBy('date', 'desc')
                ->get();

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function rainfall_records_provinces(Request $request)
    {
        $message = "Error al obtener los registros de lluvias por provincia";
        $action = "Registro de lluvias por provincia";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $company = $request->get('_company');
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');

            $permissions = $company->api_permissions ?? [];
            if (!isset($permissions['rainfall records provinces']['enabled']) || !$permissions['rainfall records provinces']['enabled']) {
                Audith::new($id_user, $action, $request->all(), 403, "No tiene permisos para acceder a los registros de lluvias por provincia");
                return response(["message" => "No tiene permisos para acceder a los registros de lluvias por provincia"], 403);
            }

            $monthsLimit = $permissions['rainfall records provinces']['months_back_limit'] ?? null;
            $maxResults = $permissions['rainfall records provinces']['max_results'] ?? null;

            // Límite de meses permitido
            if ($monthsLimit !== null) {
                // ⚠️ corregido: NO se resta 1, así se incluyen los N meses completos
                $startAllowed = now()->startOfMonth()->subMonths($monthsLimit);
                $endAllowed = now()->endOfMonth();

                $parsedFrom = $dateFrom ? Carbon::parse($dateFrom) : null;
                $parsedTo = $dateTo ? Carbon::parse($dateTo) : null;

                // Si ambas fechas están completamente fuera del rango, no mostrar nada
                if (
                    ($parsedFrom && $parsedFrom->gt($endAllowed)) ||
                    ($parsedTo && $parsedTo->lt($startAllowed))
                ) {
                    return response()->json([
                        "data" => [],
                        "message" => "Las fechas solicitadas están fuera del límite permitido de meses."
                    ], 200);
                }

                // Ajustar fechas
                $dateFrom = $parsedFrom ? max($parsedFrom, $startAllowed)->toDateString() : $startAllowed->toDateString();
                $dateTo = $parsedTo ? min($parsedTo, $endAllowed)->toDateString() : $endAllowed->toDateString();
            }

            CompanyApiUsages::create([
                'id_company' => $company->id,
                'request_name' => $action,
                'params' => $request->all(),
            ]);

            // Obtener meses/años publicados en control general de mercado
            $publishedControls = MarketGeneralControl::where('status_id', 1)->get(['month', 'year']);
            $publishedPeriods = $publishedControls->map(fn($c) => $c->year . '-' . str_pad($c->month, 2, '0', STR_PAD_LEFT))->toArray();

            $data = RainfallRecordProvince::query()
                ->whereRaw("DATE_FORMAT(date, '%Y-%m') IN (" . (count($publishedPeriods) > 0 ? implode(',', array_map(fn($p) => "'{$p}'", $publishedPeriods)) : "''" ) . ")")
                ->when($dateFrom, function ($query) use ($dateFrom) {
                    return $query->where('date', '>=', $dateFrom);
                })
                ->when($dateTo, function ($query) use ($dateTo) {
                    return $query->where('date', '<=', $dateTo);
                })
                ->when($maxResults, fn($q) => $q->limit($maxResults))
                ->orderBy('date', 'desc')
                ->get();

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function main_grain_prices(Request $request)
    {
        $message = "Error al obtener los precios de los principales granos";
        $action = "Precios de los principales granos";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $company = $request->get('_company');
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');

            $permissions = $company->api_permissions ?? [];
            if (!isset($permissions['main grain prices']['enabled']) || !$permissions['main grain prices']['enabled']) {
                Audith::new($id_user, $action, $request->all(), 403, "No tiene permisos para acceder a los precios de los principales granos");
                return response(["message" => "No tiene permisos para acceder a los precios de los principales granos"], 403);
            }

            $monthsLimit = $permissions['main grain prices']['months_back_limit'] ?? null;
            $maxResults = $permissions['main grain prices']['max_results'] ?? null;

            // Límite de meses permitido
            if ($monthsLimit !== null) {
                // ⚠️ corregido: NO se resta 1, así se incluyen los N meses completos
                $startAllowed = now()->startOfMonth()->subMonths($monthsLimit);
                $endAllowed = now()->endOfMonth();

                $parsedFrom = $dateFrom ? Carbon::parse($dateFrom) : null;
                $parsedTo = $dateTo ? Carbon::parse($dateTo) : null;

                // Si ambas fechas están completamente fuera del rango, no mostrar nada
                if (
                    ($parsedFrom && $parsedFrom->gt($endAllowed)) ||
                    ($parsedTo && $parsedTo->lt($startAllowed))
                ) {
                    return response()->json([
                        "data" => [],
                        "message" => "Las fechas solicitadas están fuera del límite permitido de meses."
                    ], 200);
                }

                // Ajustar fechas
                $dateFrom = $parsedFrom ? max($parsedFrom, $startAllowed)->toDateString() : $startAllowed->toDateString();
                $dateTo = $parsedTo ? min($parsedTo, $endAllowed)->toDateString() : $endAllowed->toDateString();
            }

            CompanyApiUsages::create([
                'id_company' => $company->id,
                'request_name' => $action,
                'params' => $request->all(),
            ]);

            // Obtener meses/años publicados en control general de mercado
            $publishedControls = MarketGeneralControl::where('status_id', 1)->get(['month', 'year']);
            $publishedPeriods = $publishedControls->map(fn($c) => $c->year . '-' . str_pad($c->month, 2, '0', STR_PAD_LEFT))->toArray();

            $data = MainGrainPrice::query()
                ->whereRaw("DATE_FORMAT(date, '%Y-%m') IN (" . (count($publishedPeriods) > 0 ? implode(',', array_map(fn($p) => "'{$p}'", $publishedPeriods)) : "''" ) . ")")
                ->when($dateFrom, function ($query) use ($dateFrom) {
                    return $query->where('date', '>=', $dateFrom);
                })
                ->when($dateTo, function ($query) use ($dateTo) {
                    return $query->where('date', '<=', $dateTo);
                })
                ->when($maxResults, fn($q) => $q->limit($maxResults))
                ->orderBy('date', 'desc')
                ->get();

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function pit_indicators(Request $request)
    {
        $message = "Error al obtener los indicadores PIT";
        $action = "Indicadores PIT";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $company = $request->get('_company');
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');

            $permissions = $company->api_permissions ?? [];
            if (!isset($permissions['pit indicators']['enabled']) || !$permissions['pit indicators']['enabled']) {
                Audith::new($id_user, $action, $request->all(), 403, "No tiene permisos para acceder a los indicadores PIT");
                return response(["message" => "No tiene permisos para acceder a los indicadores PIT"], 403);
            }

            $monthsLimit = $permissions['pit indicators']['months_back_limit'] ?? null;
            $maxResults = $permissions['pit indicators']['max_results'] ?? null;

            if ($monthsLimit !== null) {
                $startAllowed = now()->startOfMonth()->subMonths($monthsLimit);
                $endAllowed = now()->endOfMonth();

                $parsedFrom = $dateFrom ? Carbon::parse($dateFrom) : null;
                $parsedTo = $dateTo ? Carbon::parse($dateTo) : null;

                if (
                    ($parsedFrom && $parsedFrom->gt($endAllowed)) ||
                    ($parsedTo && $parsedTo->lt($startAllowed))
                ) {
                    return response()->json([
                        "data" => [],
                        "message" => "Las fechas solicitadas están fuera del límite permitido de meses."
                    ], 200);
                }

                $dateFrom = $parsedFrom ? max($parsedFrom, $startAllowed)->toDateString() : $startAllowed->toDateString();
                $dateTo = $parsedTo ? min($parsedTo, $endAllowed)->toDateString() : $endAllowed->toDateString();
            }

            CompanyApiUsages::create([
                'id_company' => $company->id,
                'request_name' => $action,
                'params' => $request->all(),
            ]);

            $data = PitIndicator::query()
                ->when($dateFrom, function ($query) use ($dateFrom) {
                    return $query->where('date', '>=', $dateFrom);
                })
                ->when($dateTo, function ($query) use ($dateTo) {
                    return $query->where('date', '<=', $dateTo);
                })
                ->when($maxResults, fn($q) => $q->limit($maxResults))
                ->orderBy('date', 'desc')
                ->get();

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function livestock_input_output_ratios(Request $request)
    {
        $message = "Error al obtener las relaciones insumo/producto ganadero";
        $action = "Relaciones insumo/producto ganadero";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $company = $request->get('_company');
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');

            $permissions = $company->api_permissions ?? [];
            if (!isset($permissions['livestock input output ratios']['enabled']) || !$permissions['livestock input output ratios']['enabled']) {
                Audith::new($id_user, $action, $request->all(), 403, "No tiene permisos para acceder a las relaciones insumo/producto ganadero");
                return response(["message" => "No tiene permisos para acceder a las relaciones insumo/producto ganadero"], 403);
            }

            $monthsLimit = $permissions['livestock input output ratios']['months_back_limit'] ?? null;
            $maxResults = $permissions['livestock input output ratios']['max_results'] ?? null;

            if ($monthsLimit !== null) {
                $startAllowed = now()->startOfMonth()->subMonths($monthsLimit);
                $endAllowed = now()->endOfMonth();

                $parsedFrom = $dateFrom ? Carbon::parse($dateFrom) : null;
                $parsedTo = $dateTo ? Carbon::parse($dateTo) : null;

                if (
                    ($parsedFrom && $parsedFrom->gt($endAllowed)) ||
                    ($parsedTo && $parsedTo->lt($startAllowed))
                ) {
                    return response()->json([
                        "data" => [],
                        "message" => "Las fechas solicitadas están fuera del límite permitido de meses."
                    ], 200);
                }

                $dateFrom = $parsedFrom ? max($parsedFrom, $startAllowed)->toDateString() : $startAllowed->toDateString();
                $dateTo = $parsedTo ? min($parsedTo, $endAllowed)->toDateString() : $endAllowed->toDateString();
            }

            CompanyApiUsages::create([
                'id_company' => $company->id,
                'request_name' => $action,
                'params' => $request->all(),
            ]);

            $data = LivestockInputOutputRatio::query()
                ->when($dateFrom, function ($query) use ($dateFrom) {
                    return $query->where('date', '>=', $dateFrom);
                })
                ->when($dateTo, function ($query) use ($dateTo) {
                    return $query->where('date', '<=', $dateTo);
                })
                ->when($maxResults, fn($q) => $q->limit($maxResults))
                ->orderBy('date', 'desc')
                ->get();

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function agricultural_input_output_relationships(Request $request)
    {
        $message = "Error al obtener las relaciones insumo/producto agrícola";
        $action = "Relaciones insumo/producto agrícola";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $company = $request->get('_company');
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');

            $permissions = $company->api_permissions ?? [];
            if (!isset($permissions['agricultural input output relationships']['enabled']) || !$permissions['agricultural input output relationships']['enabled']) {
                Audith::new($id_user, $action, $request->all(), 403, "No tiene permisos para acceder a las relaciones insumo/producto agrícola");
                return response(["message" => "No tiene permisos para acceder a las relaciones insumo/producto agrícola"], 403);
            }

            $monthsLimit = $permissions['agricultural input output relationships']['months_back_limit'] ?? null;
            $maxResults = $permissions['agricultural input output relationships']['max_results'] ?? null;

            if ($monthsLimit !== null) {
                $startAllowed = now()->startOfMonth()->subMonths($monthsLimit);
                $endAllowed = now()->endOfMonth();

                $parsedFrom = $dateFrom ? Carbon::parse($dateFrom) : null;
                $parsedTo = $dateTo ? Carbon::parse($dateTo) : null;

                if (
                    ($parsedFrom && $parsedFrom->gt($endAllowed)) ||
                    ($parsedTo && $parsedTo->lt($startAllowed))
                ) {
                    return response()->json([
                        "data" => [],
                        "message" => "Las fechas solicitadas están fuera del límite permitido de meses."
                    ], 200);
                }

                $dateFrom = $parsedFrom ? max($parsedFrom, $startAllowed)->toDateString() : $startAllowed->toDateString();
                $dateTo = $parsedTo ? min($parsedTo, $endAllowed)->toDateString() : $endAllowed->toDateString();
            }

            CompanyApiUsages::create([
                'id_company' => $company->id,
                'request_name' => $action,
                'params' => $request->all(),
            ]);

            $data = AgriculturalInputOutputRelationship::query()
                ->when($dateFrom, function ($query) use ($dateFrom) {
                    return $query->where('date', '>=', $dateFrom);
                })
                ->when($dateTo, function ($query) use ($dateTo) {
                    return $query->where('date', '<=', $dateTo);
                })
                ->when($maxResults, fn($q) => $q->limit($maxResults))
                ->orderBy('date', 'desc')
                ->get();

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function gross_margins_trend(Request $request)
    {
        $message = "Error al obtener la tendencia de márgenes brutos";
        $action = "Tendencia de márgenes brutos";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $company = $request->get('_company');
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');

            $permissions = $company->api_permissions ?? [];
            if (!isset($permissions['gross margins trend']['enabled']) || !$permissions['gross margins trend']['enabled']) {
                Audith::new($id_user, $action, $request->all(), 403, "No tiene permisos para acceder a la tendencia de márgenes brutos");
                return response(["message" => "No tiene permisos para acceder a la tendencia de márgenes brutos"], 403);
            }

            $monthsLimit = $permissions['gross margins trend']['months_back_limit'] ?? null;
            $maxResults = $permissions['gross margins trend']['max_results'] ?? null;

            if ($monthsLimit !== null) {
                $startAllowed = now()->startOfMonth()->subMonths($monthsLimit);
                $endAllowed = now()->endOfMonth();

                $parsedFrom = $dateFrom ? Carbon::parse($dateFrom) : null;
                $parsedTo = $dateTo ? Carbon::parse($dateTo) : null;

                if (
                    ($parsedFrom && $parsedFrom->gt($endAllowed)) ||
                    ($parsedTo && $parsedTo->lt($startAllowed))
                ) {
                    return response()->json([
                        "data" => [],
                        "message" => "Las fechas solicitadas están fuera del límite permitido de meses."
                    ], 200);
                }

                $dateFrom = $parsedFrom ? max($parsedFrom, $startAllowed)->toDateString() : $startAllowed->toDateString();
                $dateTo = $parsedTo ? min($parsedTo, $endAllowed)->toDateString() : $endAllowed->toDateString();
            }

            CompanyApiUsages::create([
                'id_company' => $company->id,
                'request_name' => $action,
                'params' => $request->all(),
            ]);

            $data = GrossMarginsTrend::query()
                ->when($dateFrom, function ($query) use ($dateFrom) {
                    return $query->where('date', '>=', $dateFrom);
                })
                ->when($dateTo, function ($query) use ($dateTo) {
                    return $query->where('date', '<=', $dateTo);
                })
                ->when($maxResults, fn($q) => $q->limit($maxResults))
                ->orderBy('date', 'desc')
                ->get();

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function harvest_prices(Request $request)
    {
        $message = "Error al obtener los precios de cosecha";
        $action = "Precios de cosecha";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $company = $request->get('_company');
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');

            $permissions = $company->api_permissions ?? [];
            if (!isset($permissions['harvest prices']['enabled']) || !$permissions['harvest prices']['enabled']) {
                Audith::new($id_user, $action, $request->all(), 403, "No tiene permisos para acceder a los precios de cosecha");
                return response(["message" => "No tiene permisos para acceder a los precios de cosecha"], 403);
            }

            $monthsLimit = $permissions['harvest prices']['months_back_limit'] ?? null;
            $maxResults = $permissions['harvest prices']['max_results'] ?? null;

            if ($monthsLimit !== null) {
                $startAllowed = now()->startOfMonth()->subMonths($monthsLimit);
                $endAllowed = now()->endOfMonth();

                $parsedFrom = $dateFrom ? Carbon::parse($dateFrom) : null;
                $parsedTo = $dateTo ? Carbon::parse($dateTo) : null;

                if (
                    ($parsedFrom && $parsedFrom->gt($endAllowed)) ||
                    ($parsedTo && $parsedTo->lt($startAllowed))
                ) {
                    return response()->json([
                        "data" => [],
                        "message" => "Las fechas solicitadas están fuera del límite permitido de meses."
                    ], 200);
                }

                $dateFrom = $parsedFrom ? max($parsedFrom, $startAllowed)->toDateString() : $startAllowed->toDateString();
                $dateTo = $parsedTo ? min($parsedTo, $endAllowed)->toDateString() : $endAllowed->toDateString();
            }

            CompanyApiUsages::create([
                'id_company' => $company->id,
                'request_name' => $action,
                'params' => $request->all(),
            ]);

            $data = HarvestPrices::query()
                ->when($dateFrom, function ($query) use ($dateFrom) {
                    return $query->where('date', '>=', $dateFrom);
                })
                ->when($dateTo, function ($query) use ($dateTo) {
                    return $query->where('date', '<=', $dateTo);
                })
                ->when($maxResults, fn($q) => $q->limit($maxResults))
                ->orderBy('date', 'desc')
                ->get();

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function product_prices(Request $request)
    {
        $message = "Error al obtener los precios de productos";
        $action = "Precios de productos";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $company = $request->get('_company');
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');

            $permissions = $company->api_permissions ?? [];
            if (!isset($permissions['product prices']['enabled']) || !$permissions['product prices']['enabled']) {
                Audith::new($id_user, $action, $request->all(), 403, "No tiene permisos para acceder a los precios de productos");
                return response(["message" => "No tiene permisos para acceder a los precios de productos"], 403);
            }

            $monthsLimit = $permissions['product prices']['months_back_limit'] ?? null;
            $maxResults = $permissions['product prices']['max_results'] ?? null;

            if ($monthsLimit !== null) {
                $startAllowed = now()->startOfMonth()->subMonths($monthsLimit);
                $endAllowed = now()->endOfMonth();

                $parsedFrom = $dateFrom ? Carbon::parse($dateFrom) : null;
                $parsedTo = $dateTo ? Carbon::parse($dateTo) : null;

                if (
                    ($parsedFrom && $parsedFrom->gt($endAllowed)) ||
                    ($parsedTo && $parsedTo->lt($startAllowed))
                ) {
                    return response()->json([
                        "data" => [],
                        "message" => "Las fechas solicitadas están fuera del límite permitido de meses."
                    ], 200);
                }

                $dateFrom = $parsedFrom ? max($parsedFrom, $startAllowed)->toDateString() : $startAllowed->toDateString();
                $dateTo = $parsedTo ? min($parsedTo, $endAllowed)->toDateString() : $endAllowed->toDateString();
            }

            CompanyApiUsages::create([
                'id_company' => $company->id,
                'request_name' => $action,
                'params' => $request->all(),
            ]);

            $data = ProductPrice::query()
                ->when($dateFrom, function ($query) use ($dateFrom) {
                    return $query->where('date', '>=', $dateFrom);
                })
                ->when($dateTo, function ($query) use ($dateTo) {
                    return $query->where('date', '<=', $dateTo);
                })
                ->when($maxResults, fn($q) => $q->limit($maxResults))
                ->orderBy('date', 'desc')
                ->get();

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function gross_margins(Request $request)
    {
        $message = "Error al obtener los márgenes brutos";
        $action = "Márgenes brutos";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $company = $request->get('_company');
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');

            $permissions = $company->api_permissions ?? [];
            if (!isset($permissions['gross margins']['enabled']) || !$permissions['gross margins']['enabled']) {
                Audith::new($id_user, $action, $request->all(), 403, "No tiene permisos para acceder a los márgenes brutos");
                return response(["message" => "No tiene permisos para acceder a los márgenes brutos"], 403);
            }

            $monthsLimit = $permissions['gross margins']['months_back_limit'] ?? null;
            $maxResults = $permissions['gross margins']['max_results'] ?? null;

            if ($monthsLimit !== null) {
                $startAllowed = now()->startOfMonth()->subMonths($monthsLimit);
                $endAllowed = now()->endOfMonth();

                $parsedFrom = $dateFrom ? Carbon::parse($dateFrom) : null;
                $parsedTo = $dateTo ? Carbon::parse($dateTo) : null;

                if (
                    ($parsedFrom && $parsedFrom->gt($endAllowed)) ||
                    ($parsedTo && $parsedTo->lt($startAllowed))
                ) {
                    return response()->json([
                        "data" => [],
                        "message" => "Las fechas solicitadas están fuera del límite permitido de meses."
                    ], 200);
                }

                $dateFrom = $parsedFrom ? max($parsedFrom, $startAllowed)->toDateString() : $startAllowed->toDateString();
                $dateTo = $parsedTo ? min($parsedTo, $endAllowed)->toDateString() : $endAllowed->toDateString();
            }

            CompanyApiUsages::create([
                'id_company' => $company->id,
                'request_name' => $action,
                'params' => $request->all(),
            ]);

            $data = GrossMargin::query()
                ->when($dateFrom, function ($query) use ($dateFrom) {
                    return $query->where('date', '>=', $dateFrom);
                })
                ->when($dateTo, function ($query) use ($dateTo) {
                    return $query->where('date', '<=', $dateTo);
                })
                ->when($maxResults, fn($q) => $q->limit($maxResults))
                ->orderBy('date', 'desc')
                ->get();

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function main_crops_buying_selling_traffic_light(Request $request)
    {
        $message = "Error al obtener el semáforo de compra/venta de cultivos principales";
        $action = "Semáforo de compra/venta de cultivos principales";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $company = $request->get('_company');
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');

            $permissions = $company->api_permissions ?? [];
            if (!isset($permissions['main crops buying selling traffic light']['enabled']) || !$permissions['main crops buying selling traffic light']['enabled']) {
                Audith::new($id_user, $action, $request->all(), 403, "No tiene permisos para acceder al semáforo de compra/venta de cultivos principales");
                return response(["message" => "No tiene permisos para acceder al semáforo de compra/venta de cultivos principales"], 403);
            }

            $monthsLimit = $permissions['main crops buying selling traffic light']['months_back_limit'] ?? null;
            $maxResults = $permissions['main crops buying selling traffic light']['max_results'] ?? null;

            if ($monthsLimit !== null) {
                $startAllowed = now()->startOfMonth()->subMonths($monthsLimit);
                $endAllowed = now()->endOfMonth();

                $parsedFrom = $dateFrom ? Carbon::parse($dateFrom) : null;
                $parsedTo = $dateTo ? Carbon::parse($dateTo) : null;

                if (
                    ($parsedFrom && $parsedFrom->gt($endAllowed)) ||
                    ($parsedTo && $parsedTo->lt($startAllowed))
                ) {
                    return response()->json([
                        "data" => [],
                        "message" => "Las fechas solicitadas están fuera del límite permitido de meses."
                    ], 200);
                }

                $dateFrom = $parsedFrom ? max($parsedFrom, $startAllowed)->toDateString() : $startAllowed->toDateString();
                $dateTo = $parsedTo ? min($parsedTo, $endAllowed)->toDateString() : $endAllowed->toDateString();
            }

            CompanyApiUsages::create([
                'id_company' => $company->id,
                'request_name' => $action,
                'params' => $request->all(),
            ]);

            $data = MainCropsBuyingSellingTrafficLight::query()
                ->when($dateFrom, function ($query) use ($dateFrom) {
                    return $query->where('date', '>=', $dateFrom);
                })
                ->when($dateTo, function ($query) use ($dateTo) {
                    return $query->where('date', '<=', $dateTo);
                })
                ->when($maxResults, fn($q) => $q->limit($maxResults))
                ->orderBy('date', 'desc')
                ->get();

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }
}

