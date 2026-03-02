<?php

namespace App\Http\Controllers;

use App\Models\Audith;
use App\Models\UserProfile;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserProfileController extends Controller
{
    // GET ALL
    public function index(Request $request)
    {
        $message = "Error al obtener perfiles de usuario";
        $action = "Listado de perfiles de usuario";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $data = UserProfile::with(['status'])->withCount('users')->get();

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
        $message = "Error al obtener perfil de usuario";
        $action = "Obtener perfil de usuario";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $data = UserProfile::with(['status'])->findOrFail($id);

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
        $message = "Error al crear perfil de usuario";
        $action = "Crear perfil de usuario";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $data = UserProfile::create([
                'name'      => $request->name,
                'status_id' => 1,
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
        $message = "Error al actualizar perfil de usuario";
        $action = "Actualizar perfil de usuario";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $profile = UserProfile::findOrFail($id);

            $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $profile->update([
                'name' => $request->name,
            ]);

            $data = $profile;
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
        $message = "Error al actualizar estado de perfil de usuario";
        $action = "Actualizar estado de perfil de usuario";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $profile = UserProfile::findOrFail($id);

            $request->validate([
                'status_id' => 'required|exists:status,id',
            ]);

            $profile->update(['status_id' => $request->status_id]);

            $data = $profile;
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
        $message = "Error al eliminar perfil de usuario";
        $action = "Eliminar perfil de usuario";
        $id_user = Auth::user()->id ?? null;

        try {
            $profile = UserProfile::findOrFail($id);
            $profile->delete();

            Audith::new($id_user, $action, $request->all(), 200, ['deleted_id' => $id]);

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(["message" => "Perfil de usuario eliminado correctamente"]);
    }
}
