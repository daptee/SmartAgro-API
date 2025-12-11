<?php

namespace App\Http\Controllers;

use App\Models\MagLeaseIndex;
use App\Models\Audith;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class MagLeaseIndexController extends Controller
{
    // GET ALL - Retorna todos los registros con filtros
    public function index(Request $request)
    {
        $message = "Error al obtener índices de arrendamiento MAG";
        $action = "Listado de índices de arrendamiento MAG";
        $data = null;
        $meta = null;

        try {
            $perPage = $request->query('per_page');
            $page = $request->query('page', 1);

            $query = MagLeaseIndex::query();

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
                    // Buscar en el campo JSON data, específicamente en "I.A.MAG ($)"
                    $q->where('data->I.A.MAG ($)', 'LIKE', "%{$search}%");
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
        $message = "Error al crear índice de arrendamiento MAG";
        $action = "Crear índice de arrendamiento MAG";
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
                if (!isset($dataJson['I.A.MAG ($)']) || empty($dataJson['I.A.MAG ($)'])) {
                    return response([
                        "message" => "El campo 'I.A.MAG ($)' (nombre del mes) es obligatorio en el JSON data cuando el estado es PUBLICADO."
                    ], 400);
                }

                // Validar que no exista un registro con el mismo mes y año
                $monthName = $dataJson['I.A.MAG ($)'];
                $year = date('Y', strtotime($request->date));

                $exists = MagLeaseIndex::where(function($q) use ($monthName) {
                    $q->where('data->I.A.MAG ($)', $monthName);
                })
                ->whereYear('date', $year);
            } else {
                // Si es BORRADOR (2), data y plan son opcionales
                $rules['data'] = 'nullable|json';
                $rules['id_plan'] = 'nullable|exists:plans,id';

                $request->validate($rules);

                // Si se proporciona data, validar el mes y año para evitar duplicados
                if ($request->data) {
                    $dataJson = json_decode($request->data, true);
                    if (isset($dataJson['I.A.MAG ($)']) && !empty($dataJson['I.A.MAG ($)'])) {
                        $monthName = $dataJson['I.A.MAG ($)'];
                        $year = date('Y', strtotime($request->date));

                        $exists = MagLeaseIndex::where(function($q) use ($monthName) {
                            $q->where('data->I.A.MAG ($)', $monthName);
                        })
                        ->whereYear('date', $year)
                        ->exists();

                        if ($exists) {
                            return response([
                                "message" => "Ya existe un registro para el mes '{$monthName}' del año {$year}."
                            ], 400);
                        }
                    }
                }
            }

            $data = MagLeaseIndex::create([
                'date' => $request->date,
                'data' => $request->data ? json_decode($request->data) : null,
                'id_plan' => $request->id_plan,
                'status_id' => $request->status_id,
                'id_user' => $id_user,
            ]);

            $data->load(['plan', 'status', 'user']);

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
        $message = "Error al actualizar índice de arrendamiento MAG";
        $action = "Actualizar índice de arrendamiento MAG";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $index = MagLeaseIndex::findOrFail($id);

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
                if (!isset($dataJson['I.A.MAG ($)']) || empty($dataJson['I.A.MAG ($)'])) {
                    return response([
                        "message" => "El campo 'I.A.MAG ($)' (nombre del mes) es obligatorio en el JSON data cuando el estado es PUBLICADO."
                    ], 400);
                }

                // Validar que no exista otro registro con el mismo mes y año
                $monthName = $dataJson['I.A.MAG ($)'];
                $year = date('Y', strtotime($request->date));

                $exists = MagLeaseIndex::where('id', '!=', $id)
                    ->where(function($q) use ($monthName) {
                        $q->where('data->I.A.MAG ($)', $monthName);
                    })
                    ->whereYear('date', $year);
            } else {
                // Si es BORRADOR (2), estos campos son opcionales
                $rules['data'] = 'nullable|json';
                $rules['id_plan'] = 'nullable|exists:plans,id';

                $request->validate($rules);

                // Si se proporciona data, validar el mes y año
                if ($request->data) {
                    $dataJson = json_decode($request->data, true);
                    if (isset($dataJson['I.A.MAG ($)']) && !empty($dataJson['I.A.MAG ($)'])) {
                        $monthName = $dataJson['I.A.MAG ($)'];
                        $year = date('Y', strtotime($request->date));

                        $exists = MagLeaseIndex::where('id', '!=', $id)
                            ->where(function($q) use ($monthName) {
                                $q->where('data->I.A.MAG ($)', $monthName);
                            })
                            ->whereYear('date', $year)
                            ->exists();

                        if ($exists) {
                            return response([
                                "message" => "Ya existe otro registro para el mes '{$monthName}' del año {$year}."
                            ], 400);
                        }
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
        $message = "Error al cambiar estado de índice de arrendamiento MAG";
        $action = "Cambiar estado de índice de arrendamiento MAG";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $index = MagLeaseIndex::findOrFail($id);

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
                if (!isset($dataJson['I.A.MAG ($)']) || empty($dataJson['I.A.MAG ($)'])) {
                    return response([
                        "message" => "No se puede publicar el registro. El campo 'I.A.MAG ($)' (nombre del mes) es obligatorio en el JSON data."
                    ], 400);
                }
            }

            $index->update([
                'status_id' => $request->status_id,
            ]);

            $data = $index;
            $data->load(['plan', 'status', 'user']);

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
        $message = "Error al eliminar índice de arrendamiento MAG";
        $action = "Eliminar índice de arrendamiento MAG";
        $id_user = Auth::user()->id ?? null;

        try {
            $index = MagLeaseIndex::findOrFail($id);
            $index->delete(); // Soft delete

            Audith::new($id_user, $action, $request->all(), 200, ['deleted_id' => $id]);

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(["message" => "Índice de arrendamiento MAG eliminado correctamente"]);
    }
}
