<?php

namespace App\Http\Controllers;

use App\Models\Audith;
use App\Models\Locality;
use App\Models\Province;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use function Ramsey\Uuid\v1;

class LocalityProvinceController extends Controller
{
    public function get_localities(Request $request)
    {
        $message = "Error al obtener registros";
        $action = "Listado de localidades";
        $data = null;
        $id_user = Auth::user()->id ?? null;
        try {
            $data = Locality::with('province')
                    ->when($request->province_id, function ($query) use ($request) {
                        return $query->where('province_id', '<=', $request->province_id);
                    })
                    ->get();
            Audith::new($id_user, $action, $request->all(), 200, null);
        } catch (Exception $e) {
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function get_provinces()
    {
        $message = "Error al obtener registros";
        $action = "Listado de provincias";
        $data = null;
        $id_user = Auth::user()->id ?? null;
        try {
            $data = Province::with('localities')->get();
            Audith::new($id_user, $action, null, 200, null);
        } catch (Exception $e) {
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            Audith::new($id_user, $action, null, 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }
}
