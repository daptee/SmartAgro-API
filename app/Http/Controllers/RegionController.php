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
    public function get_regions(Request $request)
    {
        $message = "Error al obtener registros";
        $action = "Listado de regiones";
        $data = null;
        $id_user = Auth::user()->id ?? null;
        try {
            $data = Region::all();
            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }
}

