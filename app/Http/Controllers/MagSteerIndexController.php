<?php

namespace App\Http\Controllers;

use App\Models\MagSteerIndex;
use App\Models\Audith;
use App\Http\Controllers\MarketGeneralControlController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class MagSteerIndexController extends Controller
{
    // GET ALL - Retorna todos los registros con filtros
    public function index(Request $request)
    {
        $message = "Error al obtener índices de novillo MAG";
        $action = "Listado de índices de novillo MAG";
        $data = null;
        $meta = null;

        try {
            $perPage = $request->query('per_page');
            $page = $request->query('page', 1);

            $query = MagSteerIndex::query();

            // Filtro por rango de fechas
            if ($request->has('date_from') && $request->date_from) {
                $query->where('date', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->where('date', '<=', $request->date_to);
            }

            // Filtro por estado
            if ($request->has('status_id') && $request->status_id) {
                $query->where('status_id', $request->status_id);
            }

            // Filtro por plan
            if ($request->has('id_plan') && $request->id_plan) {
                $query->where('id_plan', $request->id_plan);
            }

            // Campo de búsqueda por nombre del mes dentro del JSON
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    // Buscar en el campo JSON data, específicamente en "INMAG"
                    $q->where('data->INMAG', 'LIKE', "%{$search}%");
                });
            }

            // Orden por defecto (por fecha descendente)
            $query->orderBy('date', 'desc');

            // Si no se pasa per_page => devolver todo
            if (is_null($perPage)) {
                $indexes = $query->with(['plan', 'status', 'user'])->get();
                $data = $indexes;
            } else {
                $indexes = $query->with(['plan', 'status', 'user'])->paginate($perPage, ['*'], 'page', $page);
                $data = $indexes->items();
                $meta = [
                    'page' => $indexes->currentPage(),
                    'per_page' => $indexes->perPage(),
                    'total' => $indexes->total(),
                    'last_page' => $indexes->lastPage(),
                ];
            }

            Audith::new(Auth::user()->id ?? null, $action, $request->all(), 200, compact("action", "data", "meta"));

        } catch (Exception $e) {
            Audith::new(Auth::user()->id ?? null, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data", "meta"));
    }

    // POST - Crear nuevo registro
    public function store(Request $request)
    {
        $message = "Error al crear índice de novillo MAG";
        $action = "Crear índice de novillo MAG";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            // Validaciones según el estado
            $rules = [
                'date' => 'required|date',
                'status_id' => 'required|in:1,2', // 1=Publicado, 2=Borrador
            ];

            // Si el estado es PUBLICADO (1), todos los campos son obligatorios
            if ($request->status_id == 1) {
                $rules['data'] = 'required|json';
                $rules['id_plan'] = 'required|exists:plans,id';

                // Validar que el JSON tenga la estructura correcta y el nombre del mes
                $request->validate($rules);

                $dataJson = json_decode($request->data, true);
                if (!isset($dataJson['INMAG']) || empty($dataJson['INMAG'])) {
                    return response([
                        "message" => "El campo 'INMAG' (nombre del mes) es obligatorio en el JSON data cuando el estado es PUBLICADO."
                    ], 400);
                }

                // Validar que no exista un registro con el mismo mes y año
                $monthName = $dataJson['INMAG'];
                $year = date('Y', strtotime($request->date));
            } else {
                // Si es BORRADOR (2), data y plan son opcionales
                $rules['data'] = 'nullable|json';
                $rules['id_plan'] = 'nullable|exists:plans,id';

                $request->validate($rules);

                // Si se proporciona data, validar el mes y año para evitar duplicados
                if ($request->data) {
                    $dataJson = json_decode($request->data, true);
                    if (isset($dataJson['INMAG']) && !empty($dataJson['INMAG'])) {
                        $monthName = $dataJson['INMAG'];
                        $year = date('Y', strtotime($request->date));
                    }
                }
            }

            $data = MagSteerIndex::create([
                'date' => $request->date,
                'data' => $request->data ? json_decode($request->data) : null,
                'id_plan' => $request->id_plan,
                'status_id' => $request->status_id,
                'id_user' => $id_user,
            ]);

            $data->load(['plan', 'status', 'user']);

            // Sincronizar con control general de mercado
            $magMonth = (int) date('m', strtotime($data->date));
            $magYear = (int) date('Y', strtotime($data->date));
            MarketGeneralControlController::syncBlockStatus($magMonth, $magYear, 'mag_steer_index', $data->status_id == 1);

            Audith::new($id_user, $action, $request->all(), 201, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"), 201);
    }

    // PUT - Editar registro
    public function update(Request $request, $id)
    {
        $message = "Error al actualizar índice de novillo MAG";
        $action = "Actualizar índice de novillo MAG";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $index = MagSteerIndex::findOrFail($id);

            // Validaciones según el estado
            $rules = [
                'date' => 'required|date',
                'status_id' => 'required|in:1,2',
            ];

            // Si el estado es PUBLICADO (1), todos los campos son obligatorios
            if ($request->status_id == 1) {
                $rules['data'] = 'required|json';
                $rules['id_plan'] = 'required|exists:plans,id';

                $request->validate($rules);

                $dataJson = json_decode($request->data, true);
                if (!isset($dataJson['INMAG']) || empty($dataJson['INMAG'])) {
                    return response([
                        "message" => "El campo 'INMAG' (nombre del mes) es obligatorio en el JSON data cuando el estado es PUBLICADO."
                    ], 400);
                }

                // Validar que no exista otro registro con el mismo mes y año
                $monthName = $dataJson['INMAG'];
                $year = date('Y', strtotime($request->date));
            } else {
                // Si es BORRADOR (2), estos campos son opcionales
                $rules['data'] = 'nullable|json';
                $rules['id_plan'] = 'nullable|exists:plans,id';

                $request->validate($rules);

                // Si se proporciona data, validar el mes y año
                if ($request->data) {
                    $dataJson = json_decode($request->data, true);
                    if (isset($dataJson['INMAG']) && !empty($dataJson['INMAG'])) {
                        $monthName = $dataJson['INMAG'];
                        $year = date('Y', strtotime($request->date));
                    }
                }
            }

            $index->update([
                'date' => $request->date,
                'data' => $request->has('data') ? json_decode($request->data) : $index->data,
                'id_plan' => $request->id_plan,
                'status_id' => $request->status_id,
                'id_user' => $id_user,
            ]);

            $data = $index;
            $data->load(['plan', 'status', 'user']);

            // Sincronizar con control general de mercado
            $magMonth = (int) date('m', strtotime($data->date));
            $magYear = (int) date('Y', strtotime($data->date));
            MarketGeneralControlController::syncBlockStatus($magMonth, $magYear, 'mag_steer_index', $data->status_id == 1);

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // PUT - Cambiar estado del registro
    public function changeStatus(Request $request, $id)
    {
        $message = "Error al cambiar estado de índice de novillo MAG";
        $action = "Cambiar estado de índice de novillo MAG";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $index = MagSteerIndex::findOrFail($id);

            $request->validate([
                'status_id' => 'required|in:1,2',
            ]);

            // Si se cambia a PUBLICADO (1), validar que todos los datos estén completos
            if ($request->status_id == 1) {
                if (empty($index->date) || empty($index->data) || empty($index->id_plan)) {
                    return response([
                        "message" => "No se puede publicar el registro. Todos los campos deben estar completos (fecha, data y plan)."
                    ], 400);
                }

                // Validar que el JSON tenga el nombre del mes
                $dataJson = is_string($index->data) ? json_decode($index->data, true) : (array) $index->data;
                if (!isset($dataJson['INMAG']) || empty($dataJson['INMAG'])) {
                    return response([
                        "message" => "No se puede publicar el registro. El campo 'INMAG' (nombre del mes) es obligatorio en el JSON data."
                    ], 400);
                }
            }

            $index->update([
                'status_id' => $request->status_id,
            ]);

            $data = $index;
            $data->load(['plan', 'status', 'user']);

            // Sincronizar con control general de mercado
            $magMonth = (int) date('m', strtotime($data->date));
            $magYear = (int) date('Y', strtotime($data->date));
            MarketGeneralControlController::syncBlockStatus($magMonth, $magYear, 'mag_steer_index', $request->status_id == 1);

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // DELETE - Soft delete del registro
    public function destroy(Request $request, $id)
    {
        $message = "Error al eliminar índice de novillo MAG";
        $action = "Eliminar índice de novillo MAG";
        $id_user = Auth::user()->id ?? null;

        try {
            $index = MagSteerIndex::findOrFail($id);
            $index->delete(); // Soft delete

            Audith::new($id_user, $action, $request->all(), 200, ['deleted_id' => $id]);

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(["message" => "Índice de novillo MAG eliminado correctamente"]);
    }
}
