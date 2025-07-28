<?php

namespace App\Http\Controllers;

use App\Models\Audith;
use App\Models\Segment;
use Illuminate\Http\Request;
use Exception;

class SegmentController extends Controller
{
    // GET ALL - Público (sin token)
    public function index(Request $request)
    {
        $message = "Error al obtener los segmentos";
        $action = "Listado de segmentos";
        $data = null;

        try {
            $data = Segment::get();

            $data->load(['status']);

            Audith::new(null, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new(null, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

}