<?php

namespace App\Http\Controllers;

use App\Models\AdvertisingSpace;
use App\Models\Audith;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class AdvertisingSpaceController extends Controller
{
    public function index(Request $request)
    {
        $message = "Error al obtener los espacios publicitarios";
        $action = "Listado de espacios publicitarios";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $data = AdvertisingSpace::all();
            Audith::new($id_user, $action, $request->all(), 200, compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact('data'));
    }

    public function store(Request $request)
    {
        $message = "Error al guardar el espacio publicitario";
        $action = "Creación de espacio publicitario";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'data' => 'required|array',
            ]);

            $data = AdvertisingSpace::create([
                'name' => $validated['name'],
                'data' => $validated['data'],
            ]);

            Audith::new($id_user, $action, $request->all(), 201, compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact('data'), 201);
    }

    public function update(Request $request, $id)
    {
        $message = "Error al actualizar el espacio publicitario";
        $action = "Edición de espacio publicitario";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:100',
                'data' => 'sometimes|required|array',
            ]);

            $space = AdvertisingSpace::findOrFail($id);
            $space->update($validated);
            $data = $space;

            Audith::new($id_user, $action, $request->all(), 200, compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact('data'));
    }
}
