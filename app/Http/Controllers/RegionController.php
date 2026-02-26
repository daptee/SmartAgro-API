<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Models\Audith;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RegionController extends Controller
{
    // GET ALL
    public function index(Request $request)
    {
        $message = "Error al obtener registros";
        $action = "Listado de regiones";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $data = Region::with(['status'])->get();
            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    // GET ONE
    public function show(Request $request, $id)
    {
        $message = "Error al obtener región";
        $action = "Obtener región";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $data = Region::with(['status'])->findOrFail($id);

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
        $message = "Error al crear región";
        $action = "Crear región";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $request->validate([
                'region'     => 'required|string|max:255',
                'id_country' => 'required|exists:countries,id',
            ]);

            $data = Region::create([
                'region'     => $request->region,
                'id_country' => $request->id_country,
                'status_id'  => 1,
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
        $message = "Error al actualizar región";
        $action = "Actualizar región";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $region = Region::findOrFail($id);

            $request->validate([
                'region'     => 'required|string|max:255',
                'id_country' => 'required|exists:countries,id',
            ]);

            $region->update([
                'region'     => $request->region,
                'id_country' => $request->id_country,
            ]);

            $data = $region;
            $data->load(['status']);

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
        $message = "Error al actualizar estado de región";
        $action = "Actualizar estado de región";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $region = Region::findOrFail($id);

            $request->validate([
                'status_id' => 'required|exists:status,id',
            ]);

            $region->update(['status_id' => $request->status_id]);

            $data = $region;
            $data->load(['status']);

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
        $message = "Error al eliminar región";
        $action = "Eliminar región";
        $id_user = Auth::user()->id ?? null;

        try {
            $region = Region::findOrFail($id);
            $region->delete();

            Audith::new($id_user, $action, $request->all(), 200, ['deleted_id' => $id]);

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(["message" => "Región eliminada correctamente"]);
    }
}
