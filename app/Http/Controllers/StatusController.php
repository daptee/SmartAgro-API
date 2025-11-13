<?php

namespace App\Http\Controllers;

use App\Models\Audith;
use App\Models\Status;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StatusController extends Controller
{
    public function index(Request $request)
    {
        $message = "Error al obtener los estados generales";
        $action = "Listado de estados de generales";
        $data = null;
        $id_user = Auth::user()->id ?? null;
        try {
            $data = Status::get();

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));

    }
}

