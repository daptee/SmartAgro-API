<?php

namespace App\Http\Controllers;

use App\Models\Audith;
use App\Models\Plan;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlanController extends Controller
{
    // GET ALL
    public function index(Request $request)
    {
        $message = "Error al obtener planes";
        $action = "Listado de planes";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $data = Plan::all();

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
        $message = "Error al obtener plan";
        $action = "Obtener plan";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $data = Plan::findOrFail($id);

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // PUT - Editar datos del plan
    public function update(Request $request, $id)
    {
        $message = "Error al actualizar plan";
        $action = "Actualizar plan";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $plan = Plan::findOrFail($id);

            $request->validate([
                'name'            => 'required|string|max:255',
                'description'     => 'nullable|string',
                'price'           => 'nullable|array',
                'characteristics' => 'nullable|array',
            ]);

            $plan->update($request->only(['name', 'description', 'price', 'characteristics']));

            $data = $plan->fresh();

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // PUT STATUS - Editar estado del plan
    public function updateStatus(Request $request, $id)
    {
        $message = "Error al actualizar estado del plan";
        $action = "Actualizar estado de plan";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $plan = Plan::findOrFail($id);

            $request->validate([
                'status' => 'required|boolean',
            ]);

            $plan->update(['status' => $request->status]);

            $data = $plan->fresh();

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }
}
