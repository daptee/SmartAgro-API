<?php

namespace App\Http\Controllers;

use App\Models\RainfallRecordProvince;
use App\Models\Audith;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class RainfallRecordController extends Controller
{
    // GET ALL - Retorna todos los registros de lluvia con filtros
    public function index(Request $request)
    {
        $message = "Error al obtener registros de lluvia";
        $action = "Listado de registros de lluvia";
        $data = null;
        $meta = null;

        try {
            $perPage = $request->query('per_page');
            $page = $request->query('page', 1);

            $query = RainfallRecordProvince::query();

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

            // Orden por defecto (por fecha descendente)
            $query->orderBy('date', 'desc');

            // Si no se pasa per_page => devolver todo
            if (is_null($perPage)) {
                $rainfallRecords = $query->with(['plan', 'status', 'user'])->get();
                $data = $rainfallRecords;
            } else {
                $rainfallRecords = $query->with(['plan', 'status', 'user'])->paginate($perPage, ['*'], 'page', $page);
                $data = $rainfallRecords->items();
                $meta = [
                    'page' => $rainfallRecords->currentPage(),
                    'per_page' => $rainfallRecords->perPage(),
                    'total' => $rainfallRecords->total(),
                    'last_page' => $rainfallRecords->lastPage(),
                ];
            }

            Audith::new(Auth::user()->id ?? null, $action, $request->all(), 200, compact("action", "data", "meta"));

        } catch (Exception $e) {
            Audith::new(Auth::user()->id ?? null, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data", "meta"));
    }

    // POST - Crear nuevo registro de lluvia
    public function store(Request $request)
    {
        $message = "Error al crear registro de lluvia";
        $action = "Crear registro de lluvia";
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
            } else {
                // Si es BORRADOR (2), estos campos son opcionales
                $rules['data'] = 'nullable|json';
                $rules['id_plan'] = 'nullable|exists:plans,id';
            }

            $request->validate($rules);

            $data = RainfallRecordProvince::create([
                'date' => $request->date,
                'data' => $request->data,
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

    // PUT - Editar registro de lluvia
    public function update(Request $request, $id)
    {
        $message = "Error al actualizar registro de lluvia";
        $action = "Actualizar registro de lluvia";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $rainfallRecord = RainfallRecordProvince::findOrFail($id);

            // Validaciones según el estado
            $rules = [
                'date' => 'required|date',
                'status_id' => 'required|in:1,2',
            ];

            // Si el estado es PUBLICADO (1), todos los campos son obligatorios
            if ($request->status_id == 1) {
                $rules['data'] = 'required|json';
                $rules['id_plan'] = 'required|exists:plans,id';
            } else {
                // Si es BORRADOR (2), estos campos son opcionales
                $rules['data'] = 'nullable|json';
                $rules['id_plan'] = 'nullable|exists:plans,id';
            }

            $request->validate($rules);

            // Validar que si el estado es PUBLICADO, debe tener todos los campos completos
            if ($request->status_id == 1) {
                if (empty($request->date) || empty($request->data) || empty($request->id_plan)) {
                    return response([
                        "message" => "No se puede publicar el registro sin completar todos los campos obligatorios (fecha, datos y plan)."
                    ], 400);
                }
            }

            $rainfallRecord->update([
                'date' => $request->date,
                'data' => $request->has('data') ? $request->data : $rainfallRecord->data,
                'id_plan' => $request->id_plan,
                'status_id' => $request->status_id,
                'id_user' => $id_user,
            ]);

            $data = $rainfallRecord;
            $data->load(['plan', 'status', 'user']);

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // PUT - Cambiar estado del registro de lluvia
    public function changeStatus(Request $request, $id)
    {
        $message = "Error al cambiar estado del registro de lluvia";
        $action = "Cambiar estado del registro de lluvia";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $rainfallRecord = RainfallRecordProvince::findOrFail($id);

            $request->validate([
                'status_id' => 'required|in:1,2',
            ]);

            // Si se cambia a PUBLICADO (1), validar que todos los datos estén completos
            if ($request->status_id == 1) {
                if (empty($rainfallRecord->date) || empty($rainfallRecord->data) || empty($rainfallRecord->id_plan)) {
                    return response([
                        "message" => "No se puede publicar el registro. Todos los campos deben estar completos (fecha, datos y plan)."
                    ], 400);
                }
            }

            $rainfallRecord->update([
                'status_id' => $request->status_id,
            ]);

            $data = $rainfallRecord;
            $data->load(['plan', 'status', 'user']);

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // DELETE - Soft delete del registro de lluvia
    public function destroy(Request $request, $id)
    {
        $message = "Error al eliminar registro de lluvia";
        $action = "Eliminar registro de lluvia";
        $id_user = Auth::user()->id ?? null;

        try {
            $rainfallRecord = RainfallRecordProvince::findOrFail($id);
            $rainfallRecord->delete(); // Soft delete

            Audith::new($id_user, $action, $request->all(), 200, ['deleted_id' => $id]);

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(["message" => "Registro de lluvia eliminado correctamente"]);
    }
}
