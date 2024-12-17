<?php

namespace App\Http\Controllers;

use App\Models\Audith;
use Illuminate\Http\Request;
use App\Models\Country;
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

            Audith::new($id_user, "Listado de países", null, 200, null);
        } catch (Exception $e) {
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            Audith::new($id_user, "Listado de países", null, 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }
}
