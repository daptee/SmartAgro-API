<?php

namespace App\Http\Controllers;

use App\Models\Audith;
use Illuminate\Http\Request;
use App\Models\Country;
use App\Models\Plan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class GetsFunctionsController extends Controller
{
    public function countries()
    {
        $message = "Error al obtener registros";
        $data = null;
        $id_user = Auth::user()->id ?? null;
        try {
            $data = Country::where('status', 1)->get();

            Audith::new($id_user, "Listado de países", null, 200, compact("data"));
        } catch (Exception $e) {
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            Audith::new($id_user, "Listado de países", null, 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function plans()
    {
        $message = "Error al obtener registros";
        $data = null;
        try {
            $data = Plan::where('status', 1)->get();

            Audith::new(null, "Listado de planes", null, 200, compact("data"));
        } catch (Exception $e) {
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            Audith::new(null, "Listado de planes", null, 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }
}
