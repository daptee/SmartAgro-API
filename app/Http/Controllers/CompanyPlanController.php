<?php

namespace App\Http\Controllers;

use App\Models\CompanyPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Audith;
use Illuminate\Validation\Rule;
use Exception;

class CompanyPlanController extends Controller
{
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
            $dateStart = $request->query('date_start');
            $dateEnd = $request->query('date_end');

            $query = CompanyPlan::with(['company.category', 'company.locality', 'company.status', 'status']);

            // Filtros
            if (!is_null($company)) {
                $query->where('id_company', $company);
            }

            if (!is_null($status)) {
                $query->where('status_id', $status);
            }

            if ($request->filled('date_start') && $request->filled('date_end')) {
                $query->whereDate('date_start', '>=', $request->date_start)
                      ->whereDate('date_end', '<=', $request->date_end);
            }

            // Paginación
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
                'status' => 'required|in:1,2', // 1: Activo, 2: Inactivo
            ]);

            // Validar que no haya otro plan activo para la misma empresa
            $hasActive = CompanyPlan::where('id_company', $request->id_company)
                ->where('status_id', 1)
                ->exists();

            if ($hasActive && $request->status == 1) {
                return response([
                    'message' => 'La empresa ya tiene un plan activo.',
                ], 409);
            }

            $data = CompanyPlan::create([
                'id_company' => $request->id_company,
                'date_start' => $request->date_start,
                'date_end' => $request->date_end,
                'price' => $request->price,
                'data' => $request->data,
                'status_id' => $request->status,
            ]);

            $data->load(['company.category', 'company.locality', 'company.status', 'status']);

            Audith::new($id_user, $action, $request->all(), 201, compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(['message' => $message, 'error' => $e->getMessage()], 500);
        }

        return response(compact('data'), 201);
    }
}
