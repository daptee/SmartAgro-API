<?php

namespace App\Http\Controllers;

use App\Models\PriceMainActiveIngredientsProducer;
use App\Models\Audith;
use App\Http\Controllers\MarketGeneralControlController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class PriceMainActiveIngredientsProducerController extends Controller
{
    // GET ALL - Retorna todos los precios de ingredientes activos con filtros
    public function index(Request $request)
    {
        $message = "Error al obtener precios de ingredientes activos";
        $action = "Listado de precios de ingredientes activos";
        $data = null;
        $meta = null;

        try {
            $perPage = $request->query('per_page');
            $page = $request->query('page', 1);

            $query = PriceMainActiveIngredientsProducer::query();

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

            // Filtro por segmento
            if ($request->has('segment_id') && $request->segment_id) {
                $query->where('segment_id', $request->segment_id);
            }

            // Orden por defecto (por año y mes descendente)
            $query->orderBy('year', 'desc')->orderBy('month', 'desc');

            // Si no se pasa per_page => devolver todo
            if (is_null($perPage)) {
                $prices = $query->with(['plan', 'segment', 'status', 'user'])->get();
                $data = $prices;
            } else {
                $prices = $query->with(['plan', 'segment', 'status', 'user'])->paginate($perPage, ['*'], 'page', $page);
                $data = $prices->items();
                $meta = [
                    'page' => $prices->currentPage(),
                    'per_page' => $prices->perPage(),
                    'total' => $prices->total(),
                    'last_page' => $prices->lastPage(),
                ];
            }

            Audith::new(Auth::user()->id ?? null, $action, $request->all(), 200, compact("action", "data", "meta"));

        } catch (Exception $e) {
            Audith::new(Auth::user()->id ?? null, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data", "meta"));
    }

    // POST - Crear nuevo precio de ingrediente activo
    public function store(Request $request)
    {
        $message = "Error al crear precio de ingrediente activo";
        $action = "Crear precio de ingrediente activo";
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
                $rules['segment_id'] = 'nullable|exists:segments,id';

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
            } else {
                // Si es BORRADOR (2), estos campos son opcionales
                $rules['data'] = 'nullable';
                $rules['id_plan'] = 'nullable|exists:plans,id';
                $rules['segment_id'] = 'nullable|exists:segments,id';
                $request->validate($rules);
            }

            // Normalizar el campo data (Laravel manejará el cast automáticamente)
            $dataValue = $request->data;
            if (is_string($dataValue)) {
                $dataValue = json_decode($dataValue, true);
            }

            $data = PriceMainActiveIngredientsProducer::create([
                'month' => $request->month,
                'year' => $request->year,
                'date' => $request->date ?? null,
                'data' => $dataValue,
                'id_plan' => $request->id_plan,
                'segment_id' => $request->segment_id ?? null,
                'status_id' => $request->status_id,
                'id_user' => $id_user,
            ]);

            $data->load(['plan', 'segment', 'status', 'user']);

            // Sincronizar con control general de mercado
            MarketGeneralControlController::syncBlockStatus($data->month, $data->year, 'price_main_active_ingredients_producers', $data->status_id == 1);

            Audith::new($id_user, $action, $request->all(), 201, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"), 201);
    }

    // PUT - Editar precio de ingrediente activo
    public function update(Request $request, $id)
    {
        $message = "Error al actualizar precio de ingrediente activo";
        $action = "Actualizar precio de ingrediente activo";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $price = PriceMainActiveIngredientsProducer::findOrFail($id);

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
                $rules['segment_id'] = 'nullable|exists:segments,id';

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
            } else {
                // Si es BORRADOR (2), estos campos son opcionales
                $rules['data'] = 'nullable';
                $rules['id_plan'] = 'nullable|exists:plans,id';
                $rules['segment_id'] = 'nullable|exists:segments,id';
                $request->validate($rules);
            }

            // Normalizar el campo data (Laravel manejará el cast automáticamente)
            $dataValue = $request->has('data') ? $request->data : $price->data;
            if (is_string($dataValue)) {
                $dataValue = json_decode($dataValue, true);
            }

            $price->update([
                'month' => $request->month,
                'year' => $request->year,
                'date' => $request->date ?? $price->date,
                'data' => $dataValue,
                'id_plan' => $request->id_plan,
                'segment_id' => $request->segment_id ?? null,
                'status_id' => $request->status_id,
                'id_user' => $id_user,
            ]);

            $data = $price;
            $data->load(['plan', 'segment', 'status', 'user']);

            // Sincronizar con control general de mercado
            MarketGeneralControlController::syncBlockStatus($data->month, $data->year, 'price_main_active_ingredients_producers', $data->status_id == 1);

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // PUT - Cambiar estado del precio de ingrediente activo
    public function changeStatus(Request $request, $id)
    {
        $message = "Error al cambiar estado del precio de ingrediente activo";
        $action = "Cambiar estado del precio de ingrediente activo";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $price = PriceMainActiveIngredientsProducer::findOrFail($id);

            $request->validate([
                'status_id' => 'required|in:1,2',
            ]);

            // Si se cambia a PUBLICADO (1), validar que todos los datos estén completos
            if ($request->status_id == 1) {
                if (empty($price->month) || empty($price->year) || empty($price->data)) {
                    return response([
                        "message" => "No se puede publicar el precio. Todos los campos deben estar completos (mes, año y datos)."
                    ], 400);
                }
            }

            $price->update([
                'status_id' => $request->status_id,
            ]);

            $data = $price;
            $data->load(['plan', 'segment', 'status', 'user']);

            // Sincronizar con control general de mercado
            MarketGeneralControlController::syncBlockStatus($data->month, $data->year, 'price_main_active_ingredients_producers', $request->status_id == 1);

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // DELETE - Soft delete del precio de ingrediente activo
    public function destroy(Request $request, $id)
    {
        $message = "Error al eliminar precio de ingrediente activo";
        $action = "Eliminar precio de ingrediente activo";
        $id_user = Auth::user()->id ?? null;

        try {
            $price = PriceMainActiveIngredientsProducer::findOrFail($id);
            $price->delete(); // Soft delete

            Audith::new($id_user, $action, $request->all(), 200, ['deleted_id' => $id]);

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(["message" => "Precio de ingrediente activo eliminado correctamente"]);
    }
}
