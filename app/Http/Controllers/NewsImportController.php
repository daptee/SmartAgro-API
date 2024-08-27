<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\NewsImport;
use App\Models\Audith;
use Exception;
use Illuminate\Support\Facades\Log;

class NewsImportController extends Controller
{
    public function import(Request $request)
    {
        // Validar la solicitud
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);
        
        $action = "Importacion de news";
        $message = "Hubo un error durante la importación. Por favor, verifica el archivo y vuelve a intentarlo.";
        try {
            // Importar el archivo
            Excel::import(new NewsImport, $request->file('file'));

            Audith::new(null, $action, $request->all(), 200, null);
        } catch (Exception $e) {
            Audith::new(null, $action, $request->all(), 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response()->json(['message' => 'Datos importados correctamente.'], 200);
    }
    
}
