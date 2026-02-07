<?php

namespace App\Http\Controllers;

use App\Models\MainGrainPrice;
use App\Models\Audith;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class MainGrainPriceController extends Controller
{
    // GET ALL - Retorna todos los precios de granos con filtros
    public function index(Request $request)
    {
        $message = "Error al obtener precios de granos";
        $action = "Listado de precios de granos";
        $data = null;
        $meta = null;

        try {
            $perPage = $request->query('per_page');
            $page = $request->query('page', 1);

            $query = MainGrainPrice::query();

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
                $mainGrainPrices = $query->with(['plan', 'status', 'user'])->get();
                $data = $mainGrainPrices;
            } else {
                $mainGrainPrices = $query->with(['plan', 'status', 'user'])->paginate($perPage, ['*'], 'page', $page);
                $data = $mainGrainPrices->items();
                $meta = [
                    'page' => $mainGrainPrices->currentPage(),
                    'per_page' => $mainGrainPrices->perPage(),
                    'total' => $mainGrainPrices->total(),
                    'last_page' => $mainGrainPrices->lastPage(),
                ];
            }

            Audith::new(Auth::user()->id ?? null, $action, $request->all(), 200, compact("action", "data", "meta"));

        } catch (Exception $e) {
            Audith::new(Auth::user()->id ?? null, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data", "meta"));
    }

    // POST - Crear nuevo precio de grano
    public function store(Request $request)
    {
        $message = "Error al crear precio de grano";
        $action = "Crear precio de grano";
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
                $exists = MainGrainPrice::where('year', $request->year)
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

            $data = MainGrainPrice::create([
                'month' => $request->month,
                'year' => $request->year,
                'date' => $request->date ?? null,
                'data' => $dataValue,
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

    // PUT - Editar precio de grano
    public function update(Request $request, $id)
    {
        $message = "Error al actualizar precio de grano";
        $action = "Actualizar precio de grano";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $mainGrainPrice = MainGrainPrice::findOrFail($id);

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
                $exists = MainGrainPrice::where('year', $request->year)
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
            $dataValue = $request->has('data') ? $request->data : $mainGrainPrice->data;
            if (is_string($dataValue)) {
                $dataValue = json_decode($dataValue, true);
            }

            $mainGrainPrice->update([
                'month' => $request->month,
                'year' => $request->year,
                'date' => $request->date ?? $mainGrainPrice->date,
                'data' => $dataValue,
                'id_plan' => $request->id_plan,
                'status_id' => $request->status_id,
                'id_user' => $id_user,
            ]);

            $data = $mainGrainPrice;
            $data->load(['plan', 'status', 'user']);

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // PUT - Cambiar estado del precio de grano
    public function changeStatus(Request $request, $id)
    {
        $message = "Error al cambiar estado del precio de grano";
        $action = "Cambiar estado del precio de grano";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $mainGrainPrice = MainGrainPrice::findOrFail($id);

            $request->validate([
                'status_id' => 'required|in:1,2',
            ]);

            // Si se cambia a PUBLICADO (1), validar que todos los datos estén completos
            if ($request->status_id == 1) {
                if (empty($mainGrainPrice->month) || empty($mainGrainPrice->year) || empty($mainGrainPrice->data)) {
                    return response([
                        "message" => "No se puede publicar el precio. Todos los campos deben estar completos (mes, año y datos)."
                    ], 400);
                }

                // Validar que no exista otro registro publicado con el mismo mes y año
                $exists = MainGrainPrice::where('year', $mainGrainPrice->year)
                    ->where('month', $mainGrainPrice->month)
                    ->where('status_id', 1)
                    ->where('id', '!=', $id)
                    ->exists();

                if ($exists) {
                    return response([
                        "message" => "Ya existe otro registro publicado para el mes {$mainGrainPrice->month} del año {$mainGrainPrice->year}."
                    ], 400);
                }
            }

            $mainGrainPrice->update([
                'status_id' => $request->status_id,
            ]);

            $data = $mainGrainPrice;
            $data->load(['plan', 'status', 'user']);

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // DELETE - Soft delete del precio de grano
    public function destroy(Request $request, $id)
    {
        $message = "Error al eliminar precio de grano";
        $action = "Eliminar precio de grano";
        $id_user = Auth::user()->id ?? null;

        try {
            $mainGrainPrice = MainGrainPrice::findOrFail($id);
            $mainGrainPrice->delete(); // Soft delete

            Audith::new($id_user, $action, $request->all(), 200, ['deleted_id' => $id]);

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(["message" => "Precio de grano eliminado correctamente"]);
    }
}
