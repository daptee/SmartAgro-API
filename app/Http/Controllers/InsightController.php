<?php

namespace App\Http\Controllers;

use App\Models\Insight;
use App\Models\Audith;
use App\Http\Controllers\MarketGeneralControlController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class InsightController extends Controller
{
    // GET ALL - Retorna todos los insights con filtros
    public function index(Request $request)
    {
        $message = "Error al obtener insights";
        $action = "Listado de insights";
        $data = null;
        $meta = null;

        try {
            $perPage = $request->query('per_page');
            $page = $request->query('page', 1);

            $query = Insight::query();

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

            // Campo de búsqueda por título o descripción
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            // Orden por defecto (por fecha descendente)
            $query->orderBy('date', 'desc');

            // Si no se pasa per_page => devolver todo
            if (is_null($perPage)) {
                $insights = $query->with(['plan', 'status', 'user'])->get();
                $data = $insights;
            } else {
                $insights = $query->with(['plan', 'status', 'user'])->paginate($perPage, ['*'], 'page', $page);
                $data = $insights->items();
                $meta = [
                    'page' => $insights->currentPage(),
                    'per_page' => $insights->perPage(),
                    'total' => $insights->total(),
                    'last_page' => $insights->lastPage(),
                ];
            }

            Audith::new(Auth::user()->id ?? null, $action, $request->all(), 200, compact("action", "data", "meta"));

        } catch (Exception $e) {
            Audith::new(Auth::user()->id ?? null, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data", "meta"));
    }

    // POST - Crear nuevo insight
    public function store(Request $request)
    {
        $message = "Error al crear insight";
        $action = "Crear insight";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            // Validaciones según el estado
            $rules = [
                'title' => 'required|string|max:255',
                'date' => 'required|date',
                'status_id' => 'required|in:1,2', // 1=Publicado, 2=Borrador
            ];

            // Si el estado es PUBLICADO (1), todos los campos son obligatorios
            if ($request->status_id == 1) {
                $rules['description'] = 'required|string';
                $rules['icon'] = 'nullable|string';
                $rules['id_plan'] = 'required|exists:plans,id';
            } else {
                // Si es BORRADOR (2), estos campos son opcionales
                $rules['description'] = 'nullable|string';
                $rules['icon'] = 'nullable|string';
                $rules['id_plan'] = 'nullable|exists:plans,id';
            }

            $request->validate($rules);

            $data = Insight::create([
                'title' => $request->title,
                'description' => $request->description,
                'icon' => $request->icon,
                'date' => $request->date,
                'id_plan' => $request->id_plan,
                'status_id' => $request->status_id,
                'id_user' => $id_user,
            ]);

            $data->load(['plan', 'status', 'user']);

            // Sincronizar con control general de mercado
            $insightMonth = (int) date('m', strtotime($data->date));
            $insightYear = (int) date('Y', strtotime($data->date));
            MarketGeneralControlController::syncBlockStatus($insightMonth, $insightYear, 'insights', $data->status_id == 1);

            Audith::new($id_user, $action, $request->all(), 201, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"), 201);
    }

    // PUT - Editar insight
    public function update(Request $request, $id)
    {
        $message = "Error al actualizar insight";
        $action = "Actualizar insight";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $insight = Insight::findOrFail($id);

            // Validaciones según el estado
            $rules = [
                'title' => 'required|string|max:255',
                'date' => 'required|date',
                'status_id' => 'required|in:1,2',
            ];

            // Si el estado es PUBLICADO (1), todos los campos son obligatorios
            if ($request->status_id == 1) {
                $rules['description'] = 'required|string';
                $rules['icon'] = 'nullable|string';
                $rules['id_plan'] = 'required|exists:plans,id';
            } else {
                // Si es BORRADOR (2), estos campos son opcionales
                $rules['description'] = 'nullable|string';
                $rules['icon'] = 'nullable|string';
                $rules['id_plan'] = 'nullable|exists:plans,id';
            }

            $request->validate($rules);

            // Validar que si el estado es PUBLICADO, debe tener todos los campos completos
            if ($request->status_id == 1) {
                if (empty($request->title) || empty($request->description) || empty($request->id_plan)) {
                    return response([
                        "message" => "No se puede publicar el insight sin completar todos los campos obligatorios (título, descripción y plan)."
                    ], 400);
                }
            }

            $insight->update([
                'title' => $request->title,
                'description' => $request->description,
                'icon' => $request->has('icon') ? $request->icon : $insight->icon,
                'date' => $request->date,
                'id_plan' => $request->id_plan,
                'status_id' => $request->status_id,
                'id_user' => $id_user,
            ]);

            $data = $insight;
            $data->load(['plan', 'status', 'user']);

            // Sincronizar con control general de mercado
            $insightMonth = (int) date('m', strtotime($data->date));
            $insightYear = (int) date('Y', strtotime($data->date));
            MarketGeneralControlController::syncBlockStatus($insightMonth, $insightYear, 'insights', $data->status_id == 1);

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // PUT - Cambiar estado del insight
    public function changeStatus(Request $request, $id)
    {
        $message = "Error al cambiar estado del insight";
        $action = "Cambiar estado del insight";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $insight = Insight::findOrFail($id);

            $request->validate([
                'status_id' => 'required|in:1,2',
            ]);

            // Si se cambia a PUBLICADO (1), validar que todos los datos estén completos
            if ($request->status_id == 1) {
                if (empty($insight->title) || empty($insight->description) || empty($insight->date) || empty($insight->id_plan)) {
                    return response([
                        "message" => "No se puede publicar el insight. Todos los campos deben estar completos (título, descripción, fecha y plan)."
                    ], 400);
                }
            }

            $insight->update([
                'status_id' => $request->status_id,
            ]);

            $data = $insight;
            $data->load(['plan', 'status', 'user']);

            // Sincronizar con control general de mercado
            $insightMonth = (int) date('m', strtotime($data->date));
            $insightYear = (int) date('Y', strtotime($data->date));
            MarketGeneralControlController::syncBlockStatus($insightMonth, $insightYear, 'insights', $request->status_id == 1);

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // DELETE - Soft delete del insight
    public function destroy(Request $request, $id)
    {
        $message = "Error al eliminar insight";
        $action = "Eliminar insight";
        $id_user = Auth::user()->id ?? null;

        try {
            $insight = Insight::findOrFail($id);
            $insight->delete(); // Soft delete

            Audith::new($id_user, $action, $request->all(), 200, ['deleted_id' => $id]);

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(["message" => "Insight eliminado correctamente"]);
    }
}
