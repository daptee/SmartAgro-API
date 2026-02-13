<?php

namespace App\Http\Controllers;

use App\Models\MajorCrop;
use App\Models\Audith;
use App\Http\Controllers\MarketGeneralControlController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class MajorCropController extends Controller
{
    // GET ALL - Retorna todos los registros con filtros
    public function index(Request $request)
    {
        $message = "Error al obtener perspectivas de principales cultivos";
        $action = "Listado de perspectivas de principales cultivos";
        $data = null;
        $meta = null;

        try {
            $perPage = $request->query('per_page');
            $page = $request->query('page', 1);

            $query = MajorCrop::query();

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

            // Campo de búsqueda en el JSON data
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    // Buscar en el campo JSON data
                    $q->where('data', 'LIKE', "%{$search}%");
                });
            }

            // Orden por defecto (por año y mes descendente)
            $query->orderBy('year', 'desc')->orderBy('month', 'desc');

            // Si no se pasa per_page => devolver todo
            if (is_null($perPage)) {
                $crops = $query->with(['status', 'user'])->get();
                $data = $crops;
            } else {
                $crops = $query->with(['status', 'user'])->paginate($perPage, ['*'], 'page', $page);
                $data = $crops->items();
                $meta = [
                    'page' => $crops->currentPage(),
                    'per_page' => $crops->perPage(),
                    'total' => $crops->total(),
                    'last_page' => $crops->lastPage(),
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
        $message = "Error al crear perspectiva de principales cultivos";
        $action = "Crear perspectiva de principales cultivos";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            // Validaciones según el estado
            $rules = [
                'month' => 'required|integer|min:1|max:12',
                'year' => 'required|integer|min:2000|max:2100',
                'status_id' => 'required|in:1,2', // 1=Publicado, 2=Borrador
            ];

            // Si el estado es PUBLICADO (1), el campo data es obligatorio
            if ($request->status_id == 1) {
                $rules['data'] = 'required';

                $request->validate($rules);

                // Convertir data a array si viene como string o array
                $dataJson = is_string($request->data) ? json_decode($request->data, true) : $request->data;

                if (empty($dataJson)) {
                    return response([
                        "message" => "El campo 'data' debe contener información válida cuando el estado es PUBLICADO."
                    ], 400);
                }

                // Validar que no exista un registro publicado con el mismo mes y año
                $exists = MajorCrop::where('year', $request->year)
                    ->where('month', $request->month)
                    ->where('status_id', 1)
                    ->exists();

                if ($exists) {
                    return response([
                        "message" => "Ya existe un registro publicado para el mes {$request->month} del año {$request->year}."
                    ], 400);
                }
            } else {
                // Si es BORRADOR (2), data es opcional
                $rules['data'] = 'nullable';
                $request->validate($rules);
            }

            // Procesar el campo data: si es string, convertir a JSON; si es array, dejar como está
            $dataToStore = null;
            if ($request->has('data') && $request->data) {
                if (is_string($request->data)) {
                    $dataToStore = json_decode($request->data);
                } else if (is_array($request->data)) {
                    $dataToStore = $request->data;
                }
            }

            $data = MajorCrop::create([
                'month' => $request->month,
                'year' => $request->year,
                'data' => $dataToStore,
                'status_id' => $request->status_id,
                'id_user' => $id_user,
            ]);

            $data->load(['status', 'user']);

            // Sincronizar con control general de mercado
            MarketGeneralControlController::syncBlockStatus($data->month, $data->year, 'major_crops', $data->status_id == 1);

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
        $message = "Error al actualizar perspectiva de principales cultivos";
        $action = "Actualizar perspectiva de principales cultivos";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $crop = MajorCrop::findOrFail($id);

            // Validaciones según el estado
            $rules = [
                'month' => 'required|integer|min:1|max:12',
                'year' => 'required|integer|min:2000|max:2100',
                'status_id' => 'required|in:1,2', // 1=Publicado, 2=Borrador
            ];

            // Si el estado es PUBLICADO (1), el campo data es obligatorio
            if ($request->status_id == 1) {
                $rules['data'] = 'required';

                $request->validate($rules);

                // Convertir data a array si viene como string o array
                $dataJson = is_string($request->data) ? json_decode($request->data, true) : $request->data;

                if (empty($dataJson)) {
                    return response([
                        "message" => "El campo 'data' debe contener información válida cuando el estado es PUBLICADO."
                    ], 400);
                }

                // Validar que no exista otro registro publicado con el mismo mes y año
                $exists = MajorCrop::where('year', $request->year)
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
                // Si es BORRADOR (2), data es opcional
                $rules['data'] = 'nullable';
                $request->validate($rules);
            }

            // Procesar el campo data: si es string, convertir a JSON; si es array, dejar como está
            $dataToStore = $crop->data;
            if ($request->has('data')) {
                if (is_string($request->data)) {
                    $dataToStore = json_decode($request->data);
                } else if (is_array($request->data)) {
                    $dataToStore = $request->data;
                } else if (is_null($request->data)) {
                    $dataToStore = null;
                }
            }

            $crop->update([
                'month' => $request->month,
                'year' => $request->year,
                'data' => $dataToStore,
                'status_id' => $request->status_id,
                'id_user' => $id_user,
            ]);

            $data = $crop;
            $data->load(['status', 'user']);

            // Sincronizar con control general de mercado
            MarketGeneralControlController::syncBlockStatus($data->month, $data->year, 'major_crops', $data->status_id == 1);

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
        $message = "Error al cambiar estado de perspectiva de principales cultivos";
        $action = "Cambiar estado de perspectiva de principales cultivos";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $crop = MajorCrop::findOrFail($id);

            $request->validate([
                'status_id' => 'required|in:1,2',
            ]);

            // Si se cambia a PUBLICADO (1), validar que todos los datos estén completos
            if ($request->status_id == 1) {
                if (empty($crop->month) || empty($crop->year) || empty($crop->data)) {
                    return response([
                        "message" => "No se puede publicar el registro. Todos los campos deben estar completos (mes, año y data)."
                    ], 400);
                }

                // Validar que el JSON tenga contenido
                $dataJson = is_string($crop->data) ? json_decode($crop->data, true) : (array) $crop->data;
                if (empty($dataJson)) {
                    return response([
                        "message" => "No se puede publicar el registro. El campo 'data' debe contener información válida."
                    ], 400);
                }

                // Validar que no exista otro registro publicado con el mismo mes y año
                $exists = MajorCrop::where('year', $crop->year)
                    ->where('month', $crop->month)
                    ->where('status_id', 1)
                    ->where('id', '!=', $id)
                    ->exists();

                if ($exists) {
                    return response([
                        "message" => "Ya existe otro registro publicado para el mes {$crop->month} del año {$crop->year}."
                    ], 400);
                }
            }

            $crop->update([
                'status_id' => $request->status_id,
            ]);

            $data = $crop;
            $data->load(['status', 'user']);

            // Sincronizar con control general de mercado
            MarketGeneralControlController::syncBlockStatus($data->month, $data->year, 'major_crops', $request->status_id == 1);

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
        $message = "Error al eliminar perspectiva de principales cultivos";
        $action = "Eliminar perspectiva de principales cultivos";
        $id_user = Auth::user()->id ?? null;

        try {
            $crop = MajorCrop::findOrFail($id);
            $crop->delete(); // Delete (no soft delete por defecto)

            Audith::new($id_user, $action, $request->all(), 200, ['deleted_id' => $id]);

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(["message" => "Perspectiva de principales cultivos eliminada correctamente"]);
    }
}
