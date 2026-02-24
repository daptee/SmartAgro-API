<?php

namespace App\Http\Controllers;

use App\Models\RainfallRecordProvince;
use App\Models\Audith;
use App\Http\Controllers\MarketGeneralControlController;
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

            // Filtro por rango de mes y año desde
            if ($request->has('year_from') && $request->year_from) {
                $yearFrom = $request->year_from;
                $monthFrom = $request->has('month_from') && $request->month_from ? $request->month_from : 1;

                $query->where(function ($q) use ($yearFrom, $monthFrom) {
                    $q->where('year', '>', $yearFrom)
                      ->orWhere(function ($q2) use ($yearFrom, $monthFrom) {
                          $q2->where('year', $yearFrom)->where('month', '>=', $monthFrom);
                      });
                });
            }

            // Filtro por rango de mes y año hasta
            if ($request->has('year_to') && $request->year_to) {
                $yearTo = $request->year_to;
                $monthTo = $request->has('month_to') && $request->month_to ? $request->month_to : 12;

                $query->where(function ($q) use ($yearTo, $monthTo) {
                    $q->where('year', '<', $yearTo)
                      ->orWhere(function ($q2) use ($yearTo, $monthTo) {
                          $q2->where('year', $yearTo)->where('month', '<=', $monthTo);
                      });
                });
            }

            // Filtro por estado
            if ($request->has('status_id') && $request->status_id) {
                $query->where('status_id', $request->status_id);
            }

            // Filtro por plan
            if ($request->has('id_plan') && $request->id_plan) {
                $query->where('id_plan', $request->id_plan);
            }

            // Orden por defecto (por año y mes descendente)
            $query->orderBy('year', 'desc')->orderBy('month', 'desc');

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
                'month' => 'required|integer|min:1|max:12',
                'year' => 'required|integer|min:2000|max:2100',
                'status_id' => 'required|in:1,2', // 1=Publicado, 2=Borrador
            ];

            // Si el estado es PUBLICADO (1), todos los campos son obligatorios
            if ($request->status_id == 1) {
                $rules['data'] = 'required';
                $rules['id_plan'] = 'required|exists:plans,id';

                $request->validate($rules);

                // Normalizar el campo data
                $dataValue = $request->data;
                if (is_string($dataValue)) {
                    $dataValue = json_decode($dataValue, true);
                }

                if (empty($dataValue)) {
                    return response([
                        "message" => "El campo 'data' debe contener información válida cuando el estado es PUBLICADO."
                    ], 400);
                }

                // Validar que no exista un registro publicado con el mismo mes y año
                $exists = RainfallRecordProvince::where('year', $request->year)
                    ->where('month', $request->month)
                    ->where('status_id', 1)
                    ->exists();

                if ($exists) {
                    return response([
                        "message" => "Ya existe un registro publicado para el mes {$request->month} del año {$request->year}."
                    ], 400);
                }
            } else {
                // Si es BORRADOR (2), estos campos son opcionales
                $rules['data'] = 'nullable';
                $rules['id_plan'] = 'nullable|exists:plans,id';
                $request->validate($rules);
            }

            // Normalizar el campo data (Laravel manejará el cast automáticamente)
            $dataValue = $request->data;
            if (is_string($dataValue)) {
                $dataValue = json_decode($dataValue, true);
            }

            $data = RainfallRecordProvince::create([
                'month' => $request->month,
                'year' => $request->year,
                'date' => $request->date ?? null,
                'data' => $dataValue,
                'id_plan' => $request->id_plan,
                'status_id' => $request->status_id,
                'id_user' => $id_user,
            ]);

            $data->load(['plan', 'status', 'user']);

            // Sincronizar con control general de mercado
            MarketGeneralControlController::syncBlockStatus($data->month, $data->year, 'rainfall_records', $data->status_id == 1);

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
                'month' => 'required|integer|min:1|max:12',
                'year' => 'required|integer|min:2000|max:2100',
                'status_id' => 'required|in:1,2',
            ];

            // Si el estado es PUBLICADO (1), todos los campos son obligatorios
            if ($request->status_id == 1) {
                $rules['data'] = 'required';
                $rules['id_plan'] = 'required|exists:plans,id';

                $request->validate($rules);

                // Normalizar el campo data
                $dataValue = $request->data;
                if (is_string($dataValue)) {
                    $dataValue = json_decode($dataValue, true);
                }

                if (empty($dataValue)) {
                    return response([
                        "message" => "El campo 'data' debe contener información válida cuando el estado es PUBLICADO."
                    ], 400);
                }

                // Validar que no exista otro registro publicado con el mismo mes y año
                $exists = RainfallRecordProvince::where('year', $request->year)
                    ->where('month', $request->month)
                    ->where('status_id', 1)
                    ->where('id', '!=', $id)
                    ->exists();

                if ($exists) {
                    return response([
                        "message" => "Ya existe otro registro publicado para el mes {$request->month} del año {$request->year}."
                    ], 400);
                }
            } else {
                // Si es BORRADOR (2), estos campos son opcionales
                $rules['data'] = 'nullable';
                $rules['id_plan'] = 'nullable|exists:plans,id';
                $request->validate($rules);
            }

            // Normalizar el campo data (Laravel manejará el cast automáticamente)
            $dataValue = $request->has('data') ? $request->data : $rainfallRecord->data;
            if (is_string($dataValue)) {
                $dataValue = json_decode($dataValue, true);
            }

            $rainfallRecord->update([
                'month' => $request->month,
                'year' => $request->year,
                'date' => $request->date ?? $rainfallRecord->date,
                'data' => $dataValue,
                'id_plan' => $request->id_plan,
                'status_id' => $request->status_id,
                'id_user' => $id_user,
            ]);

            $data = $rainfallRecord;
            $data->load(['plan', 'status', 'user']);

            // Sincronizar con control general de mercado
            MarketGeneralControlController::syncBlockStatus($data->month, $data->year, 'rainfall_records', $data->status_id == 1);

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
                if (empty($rainfallRecord->month) || empty($rainfallRecord->year) || empty($rainfallRecord->data)) {
                    return response([
                        "message" => "No se puede publicar el registro. Todos los campos deben estar completos (mes, año y datos)."
                    ], 400);
                }

                // Validar que no exista otro registro publicado con el mismo mes y año
                $exists = RainfallRecordProvince::where('year', $rainfallRecord->year)
                    ->where('month', $rainfallRecord->month)
                    ->where('status_id', 1)
                    ->where('id', '!=', $id)
                    ->exists();

                if ($exists) {
                    return response([
                        "message" => "Ya existe otro registro publicado para el mes {$rainfallRecord->month} del año {$rainfallRecord->year}."
                    ], 400);
                }
            }

            $rainfallRecord->update([
                'status_id' => $request->status_id,
            ]);

            $data = $rainfallRecord;
            $data->load(['plan', 'status', 'user']);

            // Sincronizar con control general de mercado
            MarketGeneralControlController::syncBlockStatus($data->month, $data->year, 'rainfall_records', $request->status_id == 1);

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
