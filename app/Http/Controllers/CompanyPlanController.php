<?php

namespace App\Http\Controllers;

use App\Models\CompanyPlan;
use App\Models\CompanyPlanPublicitySetting;
use App\Models\StatusCompanyPlan;
use App\Services\PlanFinalizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Audith;
use Exception;
use Illuminate\Support\Facades\Log;

class CompanyPlanController extends Controller
{
    protected $service;
    public function __construct(PlanFinalizationService $service)
    {
        $this->service = $service;
    }

    public function finalizeExpired()
    {
        $message = "Error al finalizar planes y publicidades expiradas";
        $action = "Finalizar planes y publicidades expiradas";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $result = $this->service->finalizeExpired();

            Log::info($result);

            $data = $result;

            Audith::new($id_user, $action, [], 200, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, [], 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }
    public function index(Request $request)
    {
        $message = "Error al obtener los planes de empresa";
        $action = "Listado de planes de empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $perPage = $request->query('per_page', 10);
            $page = $request->query('page', 1);
            $company = $request->query('company');
            $status = $request->query('status');
            $dateStart = $request->query('date_start_from');
            $dateStart = $request->query('date_start_to');
            $dateEnd = $request->query('date_end_from');
            $dateEnd = $request->query('date_end_to');

            $query = CompanyPlan::with([
                'company.category',
                'company.locality',
                'company.status',
                'company.advertisingSpaces',
                'status',
                'users' => function ($query) {
                    $query->where('id_user_company_rol', 1)
                        ->with('user', 'rol');
                },
            ]);

            if (!is_null($company)) {
                $query->where('id_company', $company);
            }

            if (!is_null($status)) {
                $query->where('status_id', $status);
            }

            // 🔹 Filtro por rango de fecha de inicio
            if ($request->filled('date_start_from') || $request->filled('date_start_to')) {
                $query->where(function ($q) use ($request) {
                    if ($request->filled('date_start_from')) {
                        $q->whereDate('date_start', '>=', $request->date_start_from);
                    }
                    if ($request->filled('date_start_to')) {
                        $q->whereDate('date_start', '<=', $request->date_start_to);
                    }
                });
            }

            // 🔹 Filtro por rango de fecha de finalización
            if ($request->filled('date_end_from') || $request->filled('date_end_to')) {
                $query->where(function ($q) use ($request) {
                    if ($request->filled('date_end_from')) {
                        $q->whereDate('date_end', '>=', $request->date_end_from);
                    }
                    if ($request->filled('date_end_to')) {
                        $q->whereDate('date_end', '<=', $request->date_end_to);
                    }
                });
            }


            $plans = $query->paginate($perPage, ['*'], 'page', $page);

            $data = [
                'result' => $plans->items(),
                'meta_data' => [
                    'page' => $plans->currentPage(),
                    'per_page' => $plans->perPage(),
                    'total' => $plans->total(),
                    'last_page' => $plans->lastPage(),
                ]
            ];

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function show($id)
    {
        $message = "Error al obtener el plan de empresa";
        $action = "Detalle de plan de empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $plan = CompanyPlan::with([
                'company.category',
                'company.locality',
                'company.status',
                'company.advertisingSpaces',
                'status',
                'users.user',
                'users.rol'
            ])->find($id);

            if (!$plan) {
                return response([
                    "message" => "No se encontró el plan con ID $id"
                ], 404);
            }

            $data = $plan;

            Audith::new($id_user, $action, compact('id'), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, compact('id'), 500, $e->getMessage());
            return response([
                "message" => $message,
                "error" => $e->getMessage(),
                "line" => $e->getLine()
            ], 500);
        }

        return response(compact("data"));
    }


    public function store(Request $request)
    {
        $message = "Error al registrar plan de empresa";
        $action = "Registrar plan de empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $request->validate([
                'id_company' => 'required|exists:companies,id',
                'date_start' => 'required|date',
                'date_end' => 'required|date|after_or_equal:date_start',
                'price' => 'required|numeric|min:0',
                'data' => 'nullable|array',
                'status' => 'required|exists:status_company_plan,id', // 1: Activo, 2: Inactivo
            ]);

            $data = CompanyPlan::create([
                'id_company' => $request->id_company,
                'date_start' => $request->date_start,
                'date_end' => $request->date_end,
                'price' => $request->price,
                'data' => $request->data,
                'status_id' => $request->status,
            ]);

            CompanyPlanPublicitySetting::updateOrCreate(
                ['id_company_plan' => $data->id],
                ['show_any_ads' => 0]
            );

            $data->load(['company.category', 'company.locality', 'company.status', 'status']);

            Audith::new($id_user, $action, $request->all(), 201, compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(['message' => $message, 'error' => $e->getMessage()], 500);
        }

        return response(compact('data'), 201);
    }

    public function update(Request $request, $id)
    {
        $message = "Error al actualizar plan de empresa";
        $action = "Actualizar plan de empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $request->validate([
                'date_start' => 'required|date',
                'date_end' => 'required|date|after_or_equal:date_start',
                'price' => 'required|numeric|min:0',
                'data' => 'nullable|array',
                'status' => 'required|exists:status_company_plan,id', // 1: Activo, 2: Inactivo
            ]);

            $companyPlan = CompanyPlan::findOrFail($id);

            $companyPlan->update([
                'date_start' => $request->date_start,
                'date_end' => $request->date_end,
                'price' => $request->price,
                'data' => $request->data,
                'status_id' => $request->status,
            ]);

            $companyPlan->load(['company.category', 'company.locality', 'company.status', 'status']);

            $data = $companyPlan;

            Audith::new($id_user, $action, $request->all(), 200, compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(['message' => $message, 'error' => $e->getMessage()], 500);
        }

        return response(compact('data'));
    }

    public function companyPlanStatus(Request $request)
    {
        $message = "Error al obtener los estados de los planes de empresa";
        $action = "Listado de estados de los planes de empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;
        try {
            $data = StatusCompanyPlan::get();

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function updateCompanyPlanStatus(Request $request, $id)
    {
        $message = "Error al actualizar estado de plan de empresa";
        $action = "Actualizar estado del plan de empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $request->validate([
                'status' => 'required|exists:status_company_plan,id', // 1: Activo, 2: Inactivo
            ]);

            $companyPlan = CompanyPlan::findOrFail($id);

            $companyPlan->update([
                'status_id' => $request->status,
            ]);

            $companyPlan->load(['company.category', 'company.locality', 'company.status', 'status']);

            $data = $companyPlan;

            Audith::new($id_user, $action, $request->all(), 200, compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(['message' => $message, 'error' => $e->getMessage()], 500);
        }

        return response(compact('data'));
    }
}
