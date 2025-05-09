<?php

namespace App\Http\Controllers;

use App\Models\AdvertisingStatus;
use App\Models\Audith;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class AdvertisingStatusController extends Controller
{
    public function index(Request $request)
    {
        $message = "Error al obtener los estados de publicidad";
        $action = "Listado de estados de publicidad";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $data = AdvertisingStatus::get();

            Audith::new($id_user, $action, $request->all(), 200, compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact('data'));
    }
}
