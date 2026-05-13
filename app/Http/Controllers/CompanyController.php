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

            $data = Insight::with('iconData')
                ->whereRaw("DATE_FORMAT(date, '%Y-%m') IN (" . (count($publishedPeriods) > 0 ? implode(',', array_map(fn($p) => "'{$p}'", $publishedPeriods)) : "''" ) . ")")
                ->when($dateFrom, function ($query) use ($dateFrom) {
                    return $query->where('date', '>=', $dateFrom);
                })
                ->when($dateTo, function ($query) use ($dateTo) {
                    return $query->where('date', '<=', $dateTo);
                })
                ->when($maxResults, fn($q) => $q->limit($maxResults))
                ->orderBy('date', 'desc')
                ->get()
                ->map(function ($insight) {
                    $insight->icon = $insight->iconData?->url ?? $insight->icon;
                    unset($insight->iconData);
                    return $insight;
                });

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

            // Filtrar por períodos publicados (year-month)
            $query = MajorCrop::query()
                ->where(function ($q) use ($publishedPeriods) {
                    foreach ($publishedPeriods as $period) {
                        [$y, $m] = explode('-', $period);
                        $q->orWhere(function ($q2) use ($y, $m) {
                            $q2->where('year', (int)$y)->where('month', (int)$m);
                        });
                    }
                    if (empty($publishedPeriods)) {
                        $q->whereRaw('1 = 0');
                    }
                });

            // Filtro date_from: incluir registros cuyo año-mes >= fecha de inicio
            if ($dateFrom) {
                $from = Carbon::parse($dateFrom);
                $query->where(function ($q) use ($from) {
                    $q->where('year', '>', $from->year)
                      ->orWhere(function ($q2) use ($from) {
                          $q2->where('year', $from->year)->where('month', '>=', $from->month);
                      });
                });
            }

            // Filtro date_to: incluir registros cuyo año-mes <= fecha de fin
            if ($dateTo) {
                $to = Carbon::parse($dateTo);
                $query->where(function ($q) use ($to) {
                    $q->where('year', '<', $to->year)
                      ->orWhere(function ($q2) use ($to) {
                          $q2->where('year', $to->year)->where('month', '<=', $to->month);
                      });
                });
            }

            $records = $query
                ->when($maxResults, fn($q) => $q->limit($maxResults))
                ->orderBy('year', 'desc')
                ->orderByRaw('CAST(month AS UNSIGNED) DESC')
                ->get();

            // Cargar crops e iconos para resolver icon path por crop_id
            $cropsMap = \App\Models\Crop::whereNull('deleted_at')->get()->keyBy('id');
            $iconsMap = \App\Models\Icon::whereNull('deleted_at')->get()->keyBy('id');

            // Transformar al formato legacy: un objeto por cultivo por mes
            $data = [];
            foreach ($records as $record) {
                $date = Carbon::create($record->year, $record->month, 1)->endOfMonth()->toDateString();
                $titles = $record->data['titles'] ?? [];
                $cropRows = $record->data['data'] ?? [];

                foreach ($cropRows as $cropRow) {
                    $cropId = $cropRow['crop_id'] ?? null;
                    $crop = $cropId ? ($cropsMap[$cropId] ?? null) : null;
                    $icon = $crop ? ($iconsMap[$crop->icon] ?? null) : null;

                    $flatData = [];
                    foreach (['group_one', 'group_two', 'group_three', 'group_four'] as $group) {
                        if (!isset($cropRow[$group])) continue;
                        $groupTitle = $titles[$group]['name'] ?? $group;
                        $children = $titles[$group]['children'] ?? [];
                        $groupValues = $cropRow[$group];
                        $flatGroup = [];
                        foreach ($groupValues as $colKey => $colVal) {
                            $colTitle = $children[$colKey] ?? $colKey;
                            $flatGroup[$colTitle] = $colVal;
                        }
                        $flatData[strtolower($groupTitle)] = $flatGroup;
                    }

                    $data[] = [
                        'id'         => $record->id,
                        'id_plan'    => $record->id_plan,
                        'date'       => $date,
                        'icon'       => $icon?->url,
                        'title'      => $crop?->name,
                        'data'       => $flatData,
                        'created_at' => $record->created_at,
                        'updated_at' => $record->updated_at,
                    ];
                }
            }

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

            $query = PriceMainActiveIngredientsProducer::query()
                ->where(function ($q) use ($publishedPeriods) {
                    foreach ($publishedPeriods as $period) {
                        [$y, $m] = explode('-', $period);
                        $q->orWhere(function ($q2) use ($y, $m) {
                            $q2->where('year', (int)$y)->where('month', (int)$m);
                        });
                    }
                    if (empty($publishedPeriods)) {
                        $q->whereRaw('1 = 0');
                    }
                });

            if ($dateFrom) {
                $from = Carbon::parse($dateFrom);
                $query->where(function ($q) use ($from) {
                    $q->where('year', '>', $from->year)
                      ->orWhere(function ($q2) use ($from) {
                          $q2->where('year', $from->year)->where('month', '>=', $from->month);
                      });
                });
            }

            if ($dateTo) {
                $to = Carbon::parse($dateTo);
                $query->where(function ($q) use ($to) {
                    $q->where('year', '<', $to->year)
                      ->orWhere(function ($q2) use ($to) {
                          $q2->where('year', $to->year)->where('month', '<=', $to->month);
                      });
                });
            }

            $records = $query
                ->when($maxResults, fn($q) => $q->limit($maxResults))
                ->orderBy('year', 'desc')
                ->orderByRaw('CAST(month AS UNSIGNED) DESC')
                ->get();

            // Cargar clasificaciones para resolver nombre/short_name por classification_id
            $classificationsMap = \App\Models\Classification::whereNull('deleted_at')->get()->keyBy('id');

            // Transformar al formato legacy: un objeto por ingrediente activo por mes
            $data = [];
            foreach ($records as $record) {
                $date = Carbon::create($record->year, $record->month, 1)->endOfMonth()->toDateString();
                $seriesName = $record->data['series_name'] ?? [];
                $lastYearLabel   = $seriesName['last_year'] ?? null;
                $currentYearLabel = $seriesName['current_year'] ?? null;
                $ingredientRows = $record->data['data'] ?? [];

                foreach ($ingredientRows as $row) {
                    $classificationId = $row['classification_id'] ?? null;
                    $classification = $classificationId ? ($classificationsMap[$classificationId] ?? null) : null;

                    $flatData = [
                        'activo'               => $classification?->name,
                        'nomenclatura resumida' => $classification?->short_name,
                    ];

                    if ($lastYearLabel) {
                        $flatData[$lastYearLabel] = $row['last_year_value'] ?? null;
                    }
                    if ($currentYearLabel) {
                        $flatData[$currentYearLabel] = $row['current_year_value'] ?? null;
                    }

                    $data[] = [
                        'id'         => $record->id,
                        'id_plan'    => $record->id_plan,
                        'date'       => $date,
                        'title'      => $classification?->name,
                        'data'       => $flatData,
                        'created_at' => $record->created_at,
                        'updated_at' => $record->updated_at,
                    ];
                }
            }

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

            $query = ProducerSegmentPrice::query()
                ->where(function ($q) use ($publishedPeriods) {
                    foreach ($publishedPeriods as $period) {
                        [$y, $m] = explode('-', $period);
                        $q->orWhere(function ($q2) use ($y, $m) {
                            $q2->where('year', (int)$y)->where('month', (int)$m);
                        });
                    }
                    if (empty($publishedPeriods)) {
                        $q->whereRaw('1 = 0');
                    }
                });

            if ($dateFrom) {
                $from = Carbon::parse($dateFrom);
                $query->where(function ($q) use ($from) {
                    $q->where('year', '>', $from->year)
                      ->orWhere(function ($q2) use ($from) {
                          $q2->where('year', $from->year)->where('month', '>=', $from->month);
                      });
                });
            }

            if ($dateTo) {
                $to = Carbon::parse($dateTo);
                $query->where(function ($q) use ($to) {
                    $q->where('year', '<', $to->year)
                      ->orWhere(function ($q2) use ($to) {
                          $q2->where('year', $to->year)->where('month', '<=', $to->month);
                      });
                });
            }

            $records = $query
                ->when($maxResults, fn($q) => $q->limit($maxResults))
                ->orderBy('year', 'desc')
                ->orderByRaw('CAST(month AS UNSIGNED) DESC')
                ->get();

            // Cargar clasificaciones para resolver nombre por classification_id
            $classificationsMap = \App\Models\Classification::whereNull('deleted_at')->get()->keyBy('id');

            // Transformar al formato legacy: un objeto por segmento por mes
            $data = [];
            foreach ($records as $record) {
                $date = Carbon::create($record->year, $record->month, 1)->endOfMonth()->toDateString();
                $seriesName = $record->data['series_name'] ?? [];
                $lastYearLabel    = $seriesName['last_year'] ?? null;
                $currentYearLabel = $seriesName['current_year'] ?? null;
                $segmentRows = $record->data['data'] ?? [];

                foreach ($segmentRows as $row) {
                    $classificationId = $row['classification_id'] ?? null;
                    $classification = $classificationId ? ($classificationsMap[$classificationId] ?? null) : null;

                    $flatData = [
                        'USD/Kg o Lt' => $classification?->name,
                    ];

                    if ($lastYearLabel) {
                        $flatData[$lastYearLabel] = $row['last_year_value'] ?? null;
                    }
                    if ($currentYearLabel) {
                        $flatData[$currentYearLabel] = $row['current_year_value'] ?? null;
                    }

                    $data[] = [
                        'id'         => $record->id,
                        'id_plan'    => $record->id_plan,
                        'date'       => $date,
                        'title'      => $classification?->name,
                        'data'       => $flatData,
                        'created_at' => $record->created_at,
                        'updated_at' => $record->updated_at,
                    ];
                }
            }

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

            $query = RainfallRecordProvince::query()
                ->where(function ($q) use ($publishedPeriods) {
                    foreach ($publishedPeriods as $period) {
                        [$y, $m] = explode('-', $period);
                        $q->orWhere(function ($q2) use ($y, $m) {
                            $q2->where('year', (int)$y)->where('month', (int)$m);
                        });
                    }
                    if (empty($publishedPeriods)) {
                        $q->whereRaw('1 = 0');
                    }
                });

            if ($dateFrom) {
                $from = Carbon::parse($dateFrom);
                $query->where(function ($q) use ($from) {
                    $q->where('year', '>', $from->year)
                      ->orWhere(function ($q2) use ($from) {
                          $q2->where('year', $from->year)->where('month', '>=', $from->month);
                      });
                });
            }

            if ($dateTo) {
                $to = Carbon::parse($dateTo);
                $query->where(function ($q) use ($to) {
                    $q->where('year', '<', $to->year)
                      ->orWhere(function ($q2) use ($to) {
                          $q2->where('year', $to->year)->where('month', '<=', $to->month);
                      });
                });
            }

            $records = $query
                ->when($maxResults, fn($q) => $q->limit($maxResults))
                ->orderBy('year', 'desc')
                ->orderByRaw('CAST(month AS UNSIGNED) DESC')
                ->get();

            // Transformar al formato legacy: un objeto por provincia por mes
            $data = [];
            foreach ($records as $record) {
                $date = Carbon::create($record->year, $record->month, 1)->endOfMonth()->toDateString();
                $provinceRows = $record->data['data'] ?? [];
                $yearCurrent = $record->year % 100;         // ej. 2026 → 26
                $yearPrev    = ($record->year - 1) % 100;   // ej. 2025 → 25

                foreach ($provinceRows as $row) {
                    $provinceName = $row['state']['name'] ?? null;

                    $flatData = [
                        'REGISTRO DE LLUVIAS X PROVINCIA'          => $provinceName,
                        "PROM {$yearPrev}"                         => $row['prom_first_year']  ?? null,
                        "ACUM {$yearPrev}"                         => $row['acum_first_year']  ?? null,
                        "PROM {$yearCurrent}"                      => $row['prom_second_year'] ?? null,
                        "ACUM {$yearCurrent}"                      => $row['acum_second_year'] ?? null,
                        "Var. Acum {$yearCurrent} Vs {$yearPrev}"  => $row['var']              ?? null,
                    ];

                    $data[] = [
                        'id'         => $record->id,
                        'id_plan'    => $record->id_plan,
                        'date'       => $date,
                        'title'      => $provinceName,
                        'data'       => $flatData,
                        'created_at' => $record->created_at,
                        'updated_at' => $record->updated_at,
                    ];
                }
            }

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

            $query = MainGrainPrice::query()
                ->where(function ($q) use ($publishedPeriods) {
                    foreach ($publishedPeriods as $period) {
                        [$y, $m] = explode('-', $period);
                        $q->orWhere(function ($q2) use ($y, $m) {
                            $q2->where('year', (int)$y)->where('month', (int)$m);
                        });
                    }
                    if (empty($publishedPeriods)) {
                        $q->whereRaw('1 = 0');
                    }
                });

            if ($dateFrom) {
                $from = Carbon::parse($dateFrom);
                $query->where(function ($q) use ($from) {
                    $q->where('year', '>', $from->year)
                      ->orWhere(function ($q2) use ($from) {
                          $q2->where('year', $from->year)->where('month', '>=', $from->month);
                      });
                });
            }

            if ($dateTo) {
                $to = Carbon::parse($dateTo);
                $query->where(function ($q) use ($to) {
                    $q->where('year', '<', $to->year)
                      ->orWhere(function ($q2) use ($to) {
                          $q2->where('year', $to->year)->where('month', '<=', $to->month);
                      });
                });
            }

            $records = $query
                ->when($maxResults, fn($q) => $q->limit($maxResults))
                ->orderBy('year', 'desc')
                ->orderByRaw('CAST(month AS UNSIGNED) DESC')
                ->get();

            // Cargar crops e iconos para resolver nombre/icon por crop_id
            $cropsMap = \App\Models\Crop::whereNull('deleted_at')->get()->keyBy('id');
            $iconsMap = \App\Models\Icon::whereNull('deleted_at')->get()->keyBy('id');

            // Transformar al formato legacy: un objeto por cultivo por mes
            $data = [];
            foreach ($records as $record) {
                $date = Carbon::create($record->year, $record->month, 1)->endOfMonth()->toDateString();
                $cropRows = $record->data ?? [];

                foreach ($cropRows as $row) {
                    $cropId = $row['crop_id'] ?? null;
                    $crop   = $cropId ? ($cropsMap[$cropId] ?? null) : null;
                    $icon   = $crop ? ($iconsMap[$crop->icon] ?? null) : null;

                    $data[] = [
                        'id'         => $record->id,
                        'id_plan'    => $record->id_plan,
                        'date'       => $date,
                        'title'      => $crop?->name,
                        'icon'       => $icon?->url,
                        'data'       => [
                            'min'  => $row['min']  ?? null,
                            'max'  => $row['max']  ?? null,
                            'prom' => $row['prom'] ?? null,
                        ],
                        'created_at' => $record->created_at,
                        'updated_at' => $record->updated_at,
                    ];
                }
            }

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

            $query = PitIndicator::query();

            if ($dateFrom) {
                $from = Carbon::parse($dateFrom);
                $query->where(function ($q) use ($from) {
                    $q->where('year', '>', $from->year)
                      ->orWhere(function ($q2) use ($from) {
                          $q2->where('year', $from->year)->where('month', '>=', $from->month);
                      });
                });
            }

            if ($dateTo) {
                $to = Carbon::parse($dateTo);
                $query->where(function ($q) use ($to) {
                    $q->where('year', '<', $to->year)
                      ->orWhere(function ($q2) use ($to) {
                          $q2->where('year', $to->year)->where('month', '<=', $to->month);
                      });
                });
            }

            $records = $query
                ->when($maxResults, fn($q) => $q->limit($maxResults))
                ->orderBy('year', 'desc')
                ->orderByRaw('CAST(month AS UNSIGNED) DESC')
                ->get();

            // Cargar clasificaciones e iconos para resolver icon por classification_id
            $classificationsMap = \App\Models\Classification::whereNull('deleted_at')->get()->keyBy('id');
            $iconsMap           = \App\Models\Icon::whereNull('deleted_at')->get()->keyBy('id');
            $unitsMap           = \App\Models\UnitOfMeasure::whereNull('deleted_at')->get()->keyBy('id');

            // Transformar al formato legacy: un objeto por row por mes
            $data = [];
            foreach ($records as $record) {
                $date = Carbon::create($record->year, $record->month, 1)->endOfMonth()->toDateString();
                $rows = $record->data ?? [];

                foreach ($rows as $row) {
                    $classificationId = $row['classification_id'] ?? null;
                    $classification   = $classificationId ? ($classificationsMap[$classificationId] ?? null) : null;
                    $icon             = $classification ? ($iconsMap[$classification->id_icon] ?? null) : null;
                    $unitId           = $row['unit_of_measure_id'] ?? null;
                    $unit             = $unitId ? ($unitsMap[$unitId] ?? null) : null;

                    $data[] = [
                        'id'         => $record->id,
                        'id_plan'    => $record->id_plan,
                        'date'       => $date,
                        'Title'      => $row['label'] ?? null,
                        'icon'       => $icon?->url,
                        'data'       => [
                            'Clasificacion' => $classification?->name,
                            'Valor'  => $row['value'] ?? null,
                            'Unidad' => $unit?->name,
                            'Texto'  => $row['label'] ?? null,
                        ],
                        'created_at' => $record->created_at,
                        'updated_at' => $record->updated_at,
                    ];
                }
            }

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

            $query = LivestockInputOutputRatio::query();

            if ($dateFrom) {
                $from = Carbon::parse($dateFrom);
                $query->where(function ($q) use ($from) {
                    $q->where('year', '>', $from->year)
                      ->orWhere(function ($q2) use ($from) {
                          $q2->where('year', $from->year)->where('month', '>=', $from->month);
                      });
                });
            }

            if ($dateTo) {
                $to = Carbon::parse($dateTo);
                $query->where(function ($q) use ($to) {
                    $q->where('year', '<', $to->year)
                      ->orWhere(function ($q2) use ($to) {
                          $q2->where('year', $to->year)->where('month', '<=', $to->month);
                      });
                });
            }

            $records = $query
                ->when($maxResults, fn($q) => $q->limit($maxResults))
                ->orderBy('year', 'desc')
                ->orderByRaw('CAST(month AS UNSIGNED) DESC')
                ->get();

            // Cargar crops, products y unidades
            $cropsMap    = \App\Models\Crop::whereNull('deleted_at')->get()->keyBy('id');
            $productsMap = \App\Models\Product::whereNull('deleted_at')->get()->keyBy('id');
            $unitsMap    = \App\Models\UnitOfMeasure::whereNull('deleted_at')->get()->keyBy('id');

            // Transformar al formato legacy: un objeto por record (aplanando regions y relationships)
            $data = [];
            foreach ($records as $record) {
                $date = Carbon::create($record->year, $record->month, 1)->endOfMonth()->toDateString();
                $month = $record->year . '-' . str_pad($record->month, 2, '0', STR_PAD_LEFT);
                $regions = $record->data['regions'] ?? [];

                foreach ($regions as $regionRow) {
                    $regionId      = $regionRow['region_id'] ?? null;
                    $relationships = $regionRow['data']['relationships'] ?? [];

                    $flatData = [];
                    foreach ($relationships as $rel) {
                        $unitId  = $rel['unit_of_measure_id'] ?? null;
                        $unit    = $unitId ? ($unitsMap[$unitId] ?? null) : null;
                        $unitName = $unit?->name ?? '';

                        // Determinar label según combinación de ids
                        if (isset($rel['crop_id']) && isset($rel['product_id'])) {
                            $crop    = $cropsMap[$rel['crop_id']] ?? null;
                            $product = $productsMap[$rel['product_id']] ?? null;
                            $label   = ($crop?->name ?? '?') . ' / ' . ($product?->name ?? '?');
                        } elseif (isset($rel['product_id']) && isset($rel['product_id_2'])) {
                            $product1 = $productsMap[$rel['product_id']] ?? null;
                            $product2 = $productsMap[$rel['product_id_2']] ?? null;
                            $label    = 'Relacion ' . ($product1?->name ?? '?') . ' / ' . ($product2?->name ?? '?');
                        } else {
                            $label = 'Desconocido';
                        }

                        $flatData["{$label} ({$unitName})"] = [
                            'value'      => $rel['value'] ?? null,
                            'percentage' => $rel['percentage'] ?? null,
                        ];
                    }

                    $data[] = [
                        'id'         => $record->id,
                        'id_plan'    => $record->id_plan,
                        'date'       => $date,
                        'month'      => $month,
                        'region'     => (string)$regionId,
                        'data'       => $flatData,
                        'created_at' => $record->created_at,
                        'updated_at' => $record->updated_at,
                    ];
                }
            }

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

            $query = AgriculturalInputOutputRelationship::query();

            if ($dateFrom) {
                $from = Carbon::parse($dateFrom);
                $query->where(function ($q) use ($from) {
                    $q->where('year', '>', $from->year)
                      ->orWhere(function ($q2) use ($from) {
                          $q2->where('year', $from->year)->where('month', '>=', $from->month);
                      });
                });
            }

            if ($dateTo) {
                $to = Carbon::parse($dateTo);
                $query->where(function ($q) use ($to) {
                    $q->where('year', '<', $to->year)
                      ->orWhere(function ($q2) use ($to) {
                          $q2->where('year', $to->year)->where('month', '<=', $to->month);
                      });
                });
            }

            $records = $query
                ->when($maxResults, fn($q) => $q->limit($maxResults))
                ->orderBy('year', 'desc')
                ->orderByRaw('CAST(month AS UNSIGNED) DESC')
                ->get();

            // Cargar crops, products, classifications y unidades
            $cropsMap           = \App\Models\Crop::whereNull('deleted_at')->get()->keyBy('id');
            $productsMap        = \App\Models\Product::whereNull('deleted_at')->get()->keyBy('id');
            $classificationsMap = \App\Models\Classification::whereNull('deleted_at')->get()->keyBy('id');
            $unitsMap           = \App\Models\UnitOfMeasure::whereNull('deleted_at')->get()->keyBy('id');

            $data = [];
            foreach ($records as $record) {
                $date  = Carbon::create($record->year, $record->month, 1)->endOfMonth()->toDateString();
                $month = $record->year . '-' . str_pad($record->month, 2, '0', STR_PAD_LEFT);
                $regions = $record->data['regions'] ?? [];

                foreach ($regions as $regionRow) {
                    $regionId      = $regionRow['region_id'] ?? null;
                    $relationships = $regionRow['data']['relationships'] ?? [];

                    $flatData = [];
                    foreach ($relationships as $rel) {
                        $unitId   = $rel['unit_of_measure_id'] ?? null;
                        $unit     = $unitId ? ($unitsMap[$unitId] ?? null) : null;
                        $unitName = $unit?->name ?? '';

                        $crop     = isset($rel['crop_id']) ? ($cropsMap[$rel['crop_id']] ?? null) : null;
                        $cropName = $crop?->name ?? '?';

                        if (isset($rel['classification_id'])) {
                            $classification = $classificationsMap[$rel['classification_id']] ?? null;
                            $secondName     = $classification?->name ?? '?';
                        } elseif (isset($rel['product_id'])) {
                            $product    = $productsMap[$rel['product_id']] ?? null;
                            $secondName = $product?->name ?? '?';
                        } else {
                            $secondName = '?';
                        }

                        $label = "{$cropName}/{$secondName} ({$unitName})";

                        $flatData[$label] = [
                            'value'      => $rel['value'] ?? null,
                            'percentage' => $rel['percentage'] ?? null,
                        ];
                    }

                    $data[] = [
                        'id'         => $record->id,
                        'id_plan'    => $record->id_plan,
                        'date'       => $date,
                        'month'      => $month,
                        'region'     => (string)$regionId,
                        'data'       => $flatData,
                        'created_at' => $record->created_at,
                        'updated_at' => $record->updated_at,
                    ];
                }
            }

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

            $query = GrossMarginsTrend::query();

            if ($dateFrom) {
                $from = Carbon::parse($dateFrom);
                $query->where(function ($q) use ($from) {
                    $q->where('year', '>', $from->year)
                      ->orWhere(function ($q2) use ($from) {
                          $q2->where('year', $from->year)->where('month', '>=', $from->month);
                      });
                });
            }

            if ($dateTo) {
                $to = Carbon::parse($dateTo);
                $query->where(function ($q) use ($to) {
                    $q->where('year', '<', $to->year)
                      ->orWhere(function ($q2) use ($to) {
                          $q2->where('year', $to->year)->where('month', '<=', $to->month);
                      });
                });
            }

            $records = $query
                ->when($maxResults, fn($q) => $q->limit($maxResults))
                ->orderBy('year', 'desc')
                ->orderByRaw('CAST(month AS UNSIGNED) DESC')
                ->get();

            $cropsMap = \App\Models\Crop::whereNull('deleted_at')->get()->keyBy('id');

            // Transformar al formato legacy: un objeto por región por mes
            $data = [];
            foreach ($records as $record) {
                $date  = Carbon::create($record->year, $record->month, 1)->endOfMonth()->toDateString();
                $month = $record->year . '-' . str_pad($record->month, 2, '0', STR_PAD_LEFT);
                $regions = $record->data['regions'] ?? [];

                foreach ($regions as $regionRow) {
                    $regionId = $regionRow['region_id'] ?? null;
                    $flatData = [];

                    foreach ($regionRow['data'] ?? [] as $cropRow) {
                        $cropId = $cropRow['crop_id'] ?? null;
                        $crop   = $cropId ? ($cropsMap[$cropId] ?? null) : null;
                        if ($crop) {
                            $flatData[$crop->name] = $cropRow['value'] ?? null;
                        }
                    }

                    $data[] = [
                        'id'         => $record->id,
                        'id_plan'    => $record->id_plan,
                        'date'       => $date,
                        'month'      => $month,
                        'region'     => (string)$regionId,
                        'data'       => $flatData,
                        'created_at' => $record->created_at,
                        'updated_at' => $record->updated_at,
                    ];
                }
            }

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

            $query = HarvestPrices::query();

            if ($dateFrom) {
                $from = Carbon::parse($dateFrom);
                $query->where(function ($q) use ($from) {
                    $q->where('year', '>', $from->year)
                      ->orWhere(function ($q2) use ($from) {
                          $q2->where('year', $from->year)->where('month', '>=', $from->month);
                      });
                });
            }

            if ($dateTo) {
                $to = Carbon::parse($dateTo);
                $query->where(function ($q) use ($to) {
                    $q->where('year', '<', $to->year)
                      ->orWhere(function ($q2) use ($to) {
                          $q2->where('year', $to->year)->where('month', '<=', $to->month);
                      });
                });
            }

            $records = $query
                ->when($maxResults, fn($q) => $q->limit($maxResults))
                ->orderBy('year', 'desc')
                ->orderByRaw('CAST(month AS UNSIGNED) DESC')
                ->get();

            $cropsMap = \App\Models\Crop::whereNull('deleted_at')->get()->keyBy('id');

            $data = [];
            foreach ($records as $record) {
                $date  = Carbon::create($record->year, $record->month, 1)->endOfMonth()->toDateString();
                $month = $record->year . '-' . str_pad($record->month, 2, '0', STR_PAD_LEFT);
                $regions = $record->data['regions'] ?? [];

                foreach ($regions as $regionRow) {
                    $regionId = $regionRow['region_id'] ?? null;
                    $flatData = [];

                    foreach ($regionRow['data'] ?? [] as $cropRow) {
                        $cropId = $cropRow['crop_id'] ?? null;
                        $crop   = $cropId ? ($cropsMap[$cropId] ?? null) : null;
                        if ($crop) {
                            $flatData[$crop->name] = $cropRow['value'] ?? null;
                        }
                    }

                    $data[] = [
                        'id'         => $record->id,
                        'id_plan'    => $record->id_plan,
                        'date'       => $date,
                        'month'      => $month,
                        'region'     => (string)$regionId,
                        'data'       => $flatData,
                        'created_at' => $record->created_at,
                        'updated_at' => $record->updated_at,
                    ];
                }
            }

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

            $query = ProductPrice::query();

            if ($dateFrom) {
                $from = Carbon::parse($dateFrom);
                $query->where(function ($q) use ($from) {
                    $q->where('year', '>', $from->year)
                      ->orWhere(function ($q2) use ($from) {
                          $q2->where('year', $from->year)->where('month', '>=', $from->month);
                      });
                });
            }

            if ($dateTo) {
                $to = Carbon::parse($dateTo);
                $query->where(function ($q) use ($to) {
                    $q->where('year', '<', $to->year)
                      ->orWhere(function ($q2) use ($to) {
                          $q2->where('year', $to->year)->where('month', '<=', $to->month);
                      });
                });
            }

            $data = $query
                ->when($maxResults, fn($q) => $q->limit($maxResults))
                ->orderBy('year', 'desc')
                ->orderByRaw('CAST(month AS UNSIGNED) DESC')
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

