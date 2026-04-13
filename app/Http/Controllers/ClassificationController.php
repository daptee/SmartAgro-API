<?php

namespace App\Http\Controllers;

use App\Models\Audith;
use App\Models\Classification;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClassificationController extends Controller
{
    // GET ALL
    public function index(Request $request)
    {
        $message = "Error al obtener clasificaciones";
        $action = "Listado de clasificaciones";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $perPage = $request->input('per_page', null);
            $search = $request->input('search', '');

            $query = Classification::with(['parent', 'status', 'icon'])->orderBy('name', 'asc');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if ($perPage) {
                $data = $query->paginate($perPage);
            } else {
                $data = $query->get();
            }

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // GET ONE
    public function show(Request $request, $id)
    {
        $message = "Error al obtener clasificación";
        $action = "Obtener clasificación";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $data = Classification::with(['parent', 'children', 'status', 'icon'])->findOrFail($id);

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // POST
    public function store(Request $request)
    {
        $message = "Error al crear clasificación";
        $action = "Crear clasificación";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $request->validate([
                'name'                     => 'required|string|max:255',
                'description'              => 'nullable|string',
                'id_parent_classification' => 'nullable|exists:classifications,id',
                'id_icon'                  => 'nullable|exists:icons,id',
            ]);

            $data = Classification::create([
                'name'                     => $request->name,
                'description'              => $request->description,
                'id_parent_classification' => $request->id_parent_classification,
                'id_icon'                  => $request->input('id_icon'),
                'status_id'                => 1,
            ]);

            $data->load(['parent', 'status', 'icon']);

            Audith::new($id_user, $action, $request->all(), 201, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"), 201);
    }

    // PUT
    public function update(Request $request, $id)
    {
        $message = "Error al actualizar clasificación";
        $action = "Actualizar clasificación";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $classification = Classification::findOrFail($id);

            $request->validate([
                'name'                     => 'required|string|max:255',
                'description'              => 'nullable|string',
                'id_parent_classification' => [
                    'nullable',
                    'exists:classifications,id',
                    function ($attribute, $value, $fail) use ($id) {
                        if ($value == $id) {
                            $fail('Una clasificación no puede ser su propio padre.');
                        }
                    },
                ],
                'id_icon'                  => 'nullable|exists:icons,id',
            ]);

            $classification->update([
                'name'                     => $request->name,
                'description'              => $request->description,
                'id_parent_classification' => $request->input('id_parent_classification'),
                'id_icon'                  => $request->input('id_icon'),
            ]);

            $data = $classification;
            $data->load(['parent', 'status', 'icon']);

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // PUT STATUS
    public function updateStatus(Request $request, $id)
    {
        $message = "Error al actualizar estado de clasificación";
        $action = "Actualizar estado de clasificación";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $classification = Classification::findOrFail($id);

            $request->validate([
                'status_id' => 'required|exists:status,id',
            ]);

            $classification->update(['status_id' => $request->status_id]);

            $data = $classification;
            $data->load(['parent', 'status', 'icon']);

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // DELETE (soft delete)
    public function destroy(Request $request, $id)
    {
        $message = "Error al eliminar clasificación";
        $action = "Eliminar clasificación";
        $id_user = Auth::user()->id ?? null;

        try {
            $classification = Classification::findOrFail($id);
            $classification->delete();

            Audith::new($id_user, $action, $request->all(), 200, ['deleted_id' => $id]);

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(["message" => "Clasificación eliminada correctamente"]);
    }
}
