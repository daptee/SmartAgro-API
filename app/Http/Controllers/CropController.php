<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use App\Models\Audith;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class CropController extends Controller
{
    // GET ALL - Con paginación y búsqueda
    public function index(Request $request)
    {
        $message = "Error al obtener cultivos";
        $action = "Listado de cultivos";
        $data = null;
        $meta = null;

        try {
            $perPage = $request->get('per_page', null);
            $page = $request->get('page', 1);
            $search = $request->get('search', '');

            $query = Crop::query();

            // Filtro de búsqueda por nombre
            if ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            }

            // Ordenar por nombre
            $query->orderBy('name', 'asc');

            // Paginado o listado completo
            if ($perPage) {
                $crops = $query->paginate($perPage, ['*'], 'page', $page);
                $data = $crops->items();
                $meta = [
                    'page' => $crops->currentPage(),
                    'per_page' => $crops->perPage(),
                    'total' => $crops->total(),
                    'last_page' => $crops->lastPage(),
                ];
            } else {
                $data = $query->get();
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

    // POST - Crear nuevo cultivo
    public function store(Request $request)
    {
        $message = "Error al crear cultivo";
        $action = "Crear cultivo";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'icon' => 'required|string|max:255'
            ]);

            $data = Crop::create([
                'name' => $request->name,
                'icon' => $request->icon,
            ]);

            Audith::new($id_user, $action, $request->all(), 201, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"), 201);
    }

    // PUT - Editar cultivo
    public function update(Request $request, $id)
    {
        $message = "Error al actualizar cultivo";
        $action = "Actualizar cultivo";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $crop = Crop::findOrFail($id);

            $request->validate([
                'name' => 'required|string|max:255',
                'icon' => 'required|string|max:255',
            ]);

            $crop->update([
                'name' => $request->name,
                'icon' => $request->icon,
            ]);

            $data = $crop;

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // DELETE - Soft delete de cultivo
    public function destroy(Request $request, $id)
    {
        $message = "Error al eliminar cultivo";
        $action = "Eliminar cultivo";
        $id_user = Auth::user()->id ?? null;

        try {
            $crop = Crop::findOrFail($id);
            $crop->delete();

            Audith::new($id_user, $action, $request->all(), 200, ['deleted_id' => $id]);

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(["message" => "Cultivo eliminado correctamente"]);
    }
}
