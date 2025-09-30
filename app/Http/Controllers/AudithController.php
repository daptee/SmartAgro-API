<?php

namespace App\Http\Controllers;

use App\Models\Audith;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AudithController extends Controller
{
    public function index(Request $request)
    {
        $message = "Error al obtener auditorías";
        $action = "Listado de auditorías";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $perPage = $request->query('per_page', 10);
            $page = $request->query('page', 1);

            // Filtros opcionales
            $dateFrom = $request->query('date_from'); // YYYY-MM-DD
            $dateTo = $request->query('date_to');   // YYYY-MM-DD
            $filterAction = $request->query('action'); // buscar en request
            $module = $request->query('module');       // buscar en params o request
            $idUser = $request->query('id_user'); // filtrar por usuario específico

            $query = Audith::with(['user']);

            // Solo las auditorías del usuario autenticado (si aplica)
            if (!is_null($idUser)) {
                $query->where('id_user', $idUser);
            }

            // Rango de fechas usando columna datetime
            if (!is_null($dateFrom) && !is_null($dateTo)) {
                $query->whereBetween('created_at', [$dateFrom . " 00:00:00", $dateTo . " 23:59:59"]);
            } elseif (!is_null($dateFrom)) {
                $query->whereDate('created_at', '>=', $dateFrom);
            } elseif (!is_null($dateTo)) {
                $query->whereDate('created_at', '<=', $dateTo);
            }

            // Filtro por acción → dentro de campo request
            if (!is_null($filterAction)) {
                $query->where('action', 'like', '%' . $filterAction . '%');
            }

            // Filtro por módulo → dentro de params o request (dependiendo de cómo lo guardes)
            if (!is_null($module)) {
                $query->where('action', 'like', '%' . $module . '%');
            }

            $audits = $query->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            $data = [
                'result' => $audits->items(),
                'meta_data' => [
                    'page' => $audits->currentPage(),
                    'per_page' => $audits->perPage(),
                    'total' => $audits->total(),
                    'last_page' => $audits->lastPage(),
                ]
            ];

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response([
                "message" => $message,
                "error" => $e->getMessage(),
                "line" => $e->getLine()
            ], 500);
        }

        return response(compact("data"));
    }
}