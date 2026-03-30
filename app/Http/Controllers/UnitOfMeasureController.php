<?php

namespace App\Http\Controllers;

use App\Models\UnitOfMeasure;
use App\Models\Audith;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class UnitOfMeasureController extends Controller
{
    // GET ALL
    public function index(Request $request)
    {
        $message = "Error al obtener las unidades de medida";
        $action = "Listado de unidades de medida";
        $data = null;

        try {
            $query = UnitOfMeasure::query();

            if ($request->has('status_id') && $request->status_id) {
                $query->where('status_id', $request->status_id);
            }

            $data = $query->with(['status'])->orderBy('name')->get();

            Audith::new(Auth::user()->id ?? null, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new(Auth::user()->id ?? null, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // POST
    public function store(Request $request)
    {
        $message = "Error al crear unidad de medida";
        $action = "Crear unidad de medida";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $request->validate([
                'name'      => 'required|string|max:100',
                'status_id' => 'required|exists:status,id',
            ]);

            $data = UnitOfMeasure::create([
                'name'      => $request->name,
                'status_id' => $request->status_id,
            ]);

            $data->load(['status']);

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
        $message = "Error al actualizar unidad de medida";
        $action = "Actualizar unidad de medida";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $record = UnitOfMeasure::findOrFail($id);

            $request->validate([
                'name'      => 'required|string|max:100',
                'status_id' => 'required|exists:status,id',
            ]);

            $record->update([
                'name'      => $request->name,
                'status_id' => $request->status_id,
            ]);

            $data = $record->fresh(['status']);

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // PUT STATUS
    public function changeStatus(Request $request, $id)
    {
        $message = "Error al cambiar estado de unidad de medida";
        $action = "Cambiar estado de unidad de medida";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $record = UnitOfMeasure::findOrFail($id);

            $request->validate([
                'status_id' => 'required|exists:status,id',
            ]);

            $record->update(['status_id' => $request->status_id]);

            $data = $record->fresh(['status']);

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // DELETE
    public function destroy(Request $request, $id)
    {
        $message = "Error al eliminar unidad de medida";
        $action = "Eliminar unidad de medida";
        $id_user = Auth::user()->id ?? null;

        try {
            $record = UnitOfMeasure::findOrFail($id);
            $record->delete();

            Audith::new($id_user, $action, $request->all(), 200, ['deleted_id' => $id]);

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(["message" => "Unidad de medida eliminada correctamente"]);
    }
}
