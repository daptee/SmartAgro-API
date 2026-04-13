<?php

namespace App\Http\Controllers;

use App\Models\EconomicVariable;
use App\Models\Audith;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class EconomicVariableController extends Controller
{
    // GET ALL
    public function index(Request $request)
    {
        $message = "Error al obtener las variables económicas";
        $action = "Listado de variables económicas";
        $data = null;

        try {
            $query = EconomicVariable::query();

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
        $message = "Error al crear variable económica";
        $action = "Crear variable económica";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $request->validate([
                'name'      => 'required|string|max:100',
                'status_id' => 'required|exists:status,id',
            ]);

            $data = EconomicVariable::create([
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
        $message = "Error al actualizar variable económica";
        $action = "Actualizar variable económica";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $record = EconomicVariable::findOrFail($id);

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
        $message = "Error al cambiar estado de variable económica";
        $action = "Cambiar estado de variable económica";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $record = EconomicVariable::findOrFail($id);

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
        $message = "Error al eliminar variable económica";
        $action = "Eliminar variable económica";
        $id_user = Auth::user()->id ?? null;

        try {
            $record = EconomicVariable::findOrFail($id);
            $record->delete();

            Audith::new($id_user, $action, $request->all(), 200, ['deleted_id' => $id]);

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(["message" => "Variable económica eliminada correctamente"]);
    }
}
