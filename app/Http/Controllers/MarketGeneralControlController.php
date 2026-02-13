<?php

namespace App\Http\Controllers;

use App\Models\MarketGeneralControl;
use App\Models\Audith;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class MarketGeneralControlController extends Controller
{
    // GET ALL - Retorna todos los controles generales de mercado con filtros
    public function index(Request $request)
    {
        $message = "Error al obtener controles generales de mercado";
        $action = "Listado de controles generales de mercado";
        $data = null;
        $meta = null;

        try {
            $perPage = $request->query('per_page');
            $page = $request->query('page', 1);

            $query = MarketGeneralControl::query();

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

            // Orden por defecto (por año y mes descendente)
            $query->orderBy('year', 'desc')->orderBy('month', 'desc');

            // Si no se pasa per_page => devolver todo
            if (is_null($perPage)) {
                $controls = $query->with(['status', 'user'])->get();
                $data = $controls;
            } else {
                $controls = $query->with(['status', 'user'])->paginate($perPage, ['*'], 'page', $page);
                $data = $controls->items();
                $meta = [
                    'page' => $controls->currentPage(),
                    'per_page' => $controls->perPage(),
                    'total' => $controls->total(),
                    'last_page' => $controls->lastPage(),
                ];
            }

            Audith::new(Auth::user()->id ?? null, $action, $request->all(), 200, compact("action", "data", "meta"));

        } catch (Exception $e) {
            Audith::new(Auth::user()->id ?? null, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data", "meta"));
    }

    // GET BY ID - Retorna un control general de mercado por ID
    public function show(Request $request, $id)
    {
        $message = "Error al obtener control general de mercado";
        $action = "Detalle de control general de mercado";
        $data = null;

        try {
            $data = MarketGeneralControl::with(['status', 'user'])->findOrFail($id);

            Audith::new(Auth::user()->id ?? null, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new(Auth::user()->id ?? null, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // POST - Crear nuevo control general de mercado
    public function store(Request $request)
    {
        $message = "Error al crear control general de mercado";
        $action = "Crear control general de mercado";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $request->validate([
                'month' => 'required|integer|min:1|max:12',
                'year' => 'required|integer|min:2000|max:2100',
            ]);

            // Validar que no exista un registro con el mismo mes y año
            $exists = MarketGeneralControl::where('year', $request->year)
                ->where('month', $request->month)
                ->exists();

            if ($exists) {
                return response([
                    "message" => "Ya existe un registro para el mes {$request->month} del año {$request->year}."
                ], 400);
            }

            // Inicializar data con todos los bloques en false
            $defaultData = [
                'major_crops' => false,
                'insights' => false,
                'news' => false,
                'rainfall_records' => false,
                'main_grain_prices' => false,
                'price_main_active_ingredients_producers' => false,
                'producer_segment_prices' => false,
                'mag_lease_index' => false,
                'mag_steer_index' => false,
            ];

            $data = MarketGeneralControl::create([
                'month' => $request->month,
                'year' => $request->year,
                'data' => $defaultData,
                'status_id' => 2, // Siempre inicia como borrador
                'id_user' => $id_user,
            ]);

            $data->load(['status', 'user']);

            Audith::new($id_user, $action, $request->all(), 201, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"), 201);
    }

    // PUT - Editar mes y año del control general de mercado
    public function update(Request $request, $id)
    {
        $message = "Error al actualizar control general de mercado";
        $action = "Actualizar control general de mercado";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $control = MarketGeneralControl::findOrFail($id);

            $request->validate([
                'month' => 'required|integer|min:1|max:12',
                'year' => 'required|integer|min:2000|max:2100',
            ]);

            // Validar que no exista otro registro con el mismo mes y año
            $exists = MarketGeneralControl::where('year', $request->year)
                ->where('month', $request->month)
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return response([
                    "message" => "Ya existe otro registro para el mes {$request->month} del año {$request->year}."
                ], 400);
            }

            $control->update([
                'month' => $request->month,
                'year' => $request->year,
                'id_user' => $id_user,
            ]);

            $data = $control;
            $data->load(['status', 'user']);

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // PUT - Actualizar campo específico del JSON data (indicar si un bloque fue cargado o eliminado)
    public function updateData(Request $request, $id)
    {
        $message = "Error al actualizar datos del control general de mercado";
        $action = "Actualizar datos del control general de mercado";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $control = MarketGeneralControl::findOrFail($id);

            $request->validate([
                'block' => 'required|string|in:major_crops,insights,news,rainfall_records,main_grain_prices,price_main_active_ingredients_producers,producer_segment_prices,mag_lease_index,mag_steer_index',
                'loaded' => 'required|boolean',
            ]);

            $currentData = $control->data ?? [];
            $currentData[$request->block] = $request->loaded;

            $control->update([
                'data' => $currentData,
                'id_user' => $id_user,
            ]);

            $data = $control;
            $data->load(['status', 'user']);

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // PUT - Cambiar estado del control general de mercado
    public function changeStatus(Request $request, $id)
    {
        $message = "Error al cambiar estado del control general de mercado";
        $action = "Cambiar estado del control general de mercado";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $control = MarketGeneralControl::findOrFail($id);

            $request->validate([
                'status_id' => 'required|in:1,2',
            ]);

            $control->update([
                'status_id' => $request->status_id,
            ]);

            $data = $control;
            $data->load(['status', 'user']);

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // DELETE - Soft delete del control general de mercado
    public function destroy(Request $request, $id)
    {
        $message = "Error al eliminar control general de mercado";
        $action = "Eliminar control general de mercado";
        $id_user = Auth::user()->id ?? null;

        try {
            $control = MarketGeneralControl::findOrFail($id);
            $control->delete(); // Soft delete

            Audith::new($id_user, $action, $request->all(), 200, ['deleted_id' => $id]);

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(["message" => "Control general de mercado eliminado correctamente"]);
    }

    /**
     * Método estático helper para actualizar el JSON data de la tabla madre
     * cuando un bloque cambia de estado (publicado/borrador)
     */
    public static function syncBlockStatus($month, $year, $blockName, $isPublished)
    {
        $control = MarketGeneralControl::where('month', $month)
            ->where('year', $year)
            ->first();

        if ($control) {
            $currentData = $control->data ?? [];
            $currentData[$blockName] = $isPublished;
            $control->update(['data' => $currentData]);
        }
    }
}
