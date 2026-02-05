<?php

namespace App\Http\Controllers;

use App\Models\ActiveIngredient;
use App\Models\Audith;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class ActiveIngredientController extends Controller
{
    // GET ALL - Con paginación y búsqueda
    public function index(Request $request)
    {
        $message = "Error al obtener ingredientes activos";
        $action = "Listado de ingredientes activos";
        $data = null;
        $meta = null;

        try {
            $perPage = $request->get('per_page', null);
            $page = $request->get('page', 1);
            $search = $request->get('search', '');

            $query = ActiveIngredient::query();

            // Filtro de búsqueda por nombre o nombre abreviado
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('abbreviated_name', 'like', '%' . $search . '%');
                });
            }

            // Ordenar por nombre
            $query->orderBy('name', 'asc');

            // Paginado o listado completo
            if ($perPage) {
                $activeIngredients = $query->with(['segment'])->paginate($perPage, ['*'], 'page', $page);
                $data = $activeIngredients->items();
                $meta = [
                    'page' => $activeIngredients->currentPage(),
                    'per_page' => $activeIngredients->perPage(),
                    'total' => $activeIngredients->total(),
                    'last_page' => $activeIngredients->lastPage(),
                ];
            } else {
                $data = $query->with(['segment'])->get();
            }

            Audith::new(null, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new(null, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        if ($meta) {
            return response(compact("data", "meta"));
        }

        return response(compact("data"));
    }

    // POST - Crear nuevo ingrediente activo
    public function store(Request $request)
    {
        $message = "Error al crear ingrediente activo";
        $action = "Crear ingrediente activo";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'abbreviated_name' => 'required|string|max:100',
                'segment_id' => 'required|exists:segments,id'
            ]);

            $data = ActiveIngredient::create([
                'name' => $request->name,
                'abbreviated_name' => $request->abbreviated_name,
                'segment_id' => $request->segment_id,
            ]);

            $data->load(['segment']);

            Audith::new($id_user, $action, $request->all(), 201, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"), 201);
    }

    // PUT - Editar ingrediente activo
    public function update(Request $request, $id)
    {
        $message = "Error al actualizar ingrediente activo";
        $action = "Actualizar ingrediente activo";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $activeIngredient = ActiveIngredient::findOrFail($id);

            $request->validate([
                'name' => 'required|string|max:255',
                'abbreviated_name' => 'required|string|max:100',
                'segment_id' => 'required|exists:segments,id'
            ]);

            $activeIngredient->update([
                'name' => $request->name,
                'abbreviated_name' => $request->abbreviated_name,
                'segment_id' => $request->segment_id,
            ]);

            $data = $activeIngredient;
            $data->load(['segment']);

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // DELETE - Soft delete de ingrediente activo
    public function destroy(Request $request, $id)
    {
        $message = "Error al eliminar ingrediente activo";
        $action = "Eliminar ingrediente activo";
        $id_user = Auth::user()->id ?? null;

        try {
            $activeIngredient = ActiveIngredient::findOrFail($id);
            $activeIngredient->delete();

            Audith::new($id_user, $action, $request->all(), 200, ['deleted_id' => $id]);

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(["message" => "Ingrediente activo eliminado correctamente"]);
    }
}
