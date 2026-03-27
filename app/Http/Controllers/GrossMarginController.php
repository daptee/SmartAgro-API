<?php

namespace App\Http\Controllers;

use App\Models\GrossMargin;
use App\Models\Audith;
use App\Http\Controllers\BusinessIndicatorControlController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class GrossMarginController extends Controller
{
    // GET ALL
    public function index(Request $request)
    {
        $message = "Error al obtener márgenes brutos";
        $action = "Listado de márgenes brutos";
        $data = null;
        $meta = null;

        try {
            $perPage = $request->query('per_page');
            $page = $request->query('page', 1);

            $query = GrossMargin::query();

            if ($request->has('month') && $request->month) {
                $query->where('month', $request->month);
            }

            if ($request->has('year') && $request->year) {
                $query->where('year', $request->year);
            }

            if ($request->has('status_id') && $request->status_id) {
                $query->where('status_id', $request->status_id);
            }

            if ($request->has('id_plan') && $request->id_plan) {
                $query->where('id_plan', $request->id_plan);
            }

            $query->orderBy('year', 'desc')->orderBy('month', 'desc');

            if (is_null($perPage)) {
                $data = $query->with(['plan', 'status', 'user'])->get();
            } else {
                $records = $query->with(['plan', 'status', 'user'])->paginate($perPage, ['*'], 'page', $page);
                $data = $records->items();
                $meta = [
                    'page'     => $records->currentPage(),
                    'per_page' => $records->perPage(),
                    'total'    => $records->total(),
                    'last_page'=> $records->lastPage(),
                ];
            }

            Audith::new(Auth::user()->id ?? null, $action, $request->all(), 200, compact("data", "meta"));

        } catch (Exception $e) {
            Audith::new(Auth::user()->id ?? null, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data", "meta"));
    }

    // POST
    public function store(Request $request)
    {
        $message = "Error al crear margen bruto";
        $action = "Crear margen bruto";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $rules = [
                'month'     => 'required|integer|min:1|max:12',
                'year'      => 'required|integer|digits:4',
                'status_id' => 'required|in:1,2',
                'id_plan'   => 'required|exists:plans,id',
                'data'      => 'required|array',
            ];

            $request->validate($rules);

            $data = GrossMargin::create([
                'month'     => $request->month,
                'year'      => $request->year,
                'status_id' => $request->status_id,
                'id_plan'   => $request->id_plan,
                'data'      => $request->input('data'),
                'id_user'   => $id_user,
            ]);

            $data->load(['plan', 'status', 'user']);

            BusinessIndicatorControlController::syncBlockStatus($request->month, $request->year, 'gross_margin', $request->status_id == 1);

            Audith::new($id_user, $action, $request->all(), 201, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"), 201);
    }

    // PUT
    public function update(Request $request, $id)
    {
        $message = "Error al actualizar margen bruto";
        $action = "Actualizar margen bruto";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $record = GrossMargin::findOrFail($id);

            $rules = [
                'month'     => 'required|integer|min:1|max:12',
                'year'      => 'required|integer|digits:4',
                'status_id' => 'required|in:1,2',
                'id_plan'   => 'required|exists:plans,id',
                'data'      => 'required|array',
            ];

            $request->validate($rules);

            $record->update([
                'month'     => $request->month,
                'year'      => $request->year,
                'status_id' => $request->status_id,
                'id_plan'   => $request->id_plan,
                'data'      => $request->input('data'),
                'id_user'   => $id_user,
            ]);

            $data = $record->fresh(['plan', 'status', 'user']);

            BusinessIndicatorControlController::syncBlockStatus($request->month, $request->year, 'gross_margin', $request->status_id == 1);

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // PUT STATUS
    public function changeStatus(Request $request, $id)
    {
        $message = "Error al cambiar estado de margen bruto";
        $action = "Cambiar estado de margen bruto";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $record = GrossMargin::findOrFail($id);

            $request->validate([
                'status_id' => 'required|in:1,2',
            ]);

            if ($request->status_id == 1) {
                if (empty($record->month) || empty($record->year) || empty($record->data) || empty($record->id_plan)) {
                    return response([
                        "message" => "No se puede publicar el registro. Todos los campos deben estar completos (month, year, data y plan)."
                    ], 400);
                }
            }

            $record->update(['status_id' => $request->status_id]);

            $data = $record->fresh(['plan', 'status', 'user']);

            BusinessIndicatorControlController::syncBlockStatus($data->month, $data->year, 'gross_margin', $request->status_id == 1);

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // DELETE
    public function destroy(Request $request, $id)
    {
        $message = "Error al eliminar margen bruto";
        $action = "Eliminar margen bruto";
        $id_user = Auth::user()->id ?? null;

        try {
            $record = GrossMargin::findOrFail($id);
            $record->delete();

            Audith::new($id_user, $action, $request->all(), 200, ['deleted_id' => $id]);

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(["message" => "Margen bruto eliminado correctamente"]);
    }
}
