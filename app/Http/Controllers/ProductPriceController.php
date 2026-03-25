<?php

namespace App\Http\Controllers;

use App\Models\ProductPrice;
use App\Models\Audith;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class ProductPriceController extends Controller
{
    // GET ALL
    public function index(Request $request)
    {
        $message = "Error al obtener precios de productos";
        $action = "Listado de precios de productos";
        $data = null;
        $meta = null;

        try {
            $perPage = $request->query('per_page');
            $page = $request->query('page', 1);

            $query = ProductPrice::query();

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

            if ($request->has('segment_id') && $request->segment_id) {
                $query->where('segment_id', $request->segment_id);
            }

            $query->orderBy('year', 'desc')->orderBy('month', 'desc');

            if (is_null($perPage)) {
                $data = $query->with(['plan', 'segment', 'status', 'user'])->get();
            } else {
                $records = $query->with(['plan', 'segment', 'status', 'user'])->paginate($perPage, ['*'], 'page', $page);
                $data = $records->items();
                $meta = [
                    'page'      => $records->currentPage(),
                    'per_page'  => $records->perPage(),
                    'total'     => $records->total(),
                    'last_page' => $records->lastPage(),
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
        $message = "Error al crear precio de producto";
        $action = "Crear precio de producto";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $request->validate([
                'month'      => 'required|integer|min:1|max:12',
                'year'       => 'required|integer|digits:4',
                'status_id'  => 'required|in:1,2',
                'id_plan'    => 'required|exists:plans,id',
                'segment_id' => 'required|exists:segments,id',
                'data'       => 'required|array',
            ]);

            $data = ProductPrice::create([
                'month'      => $request->month,
                'year'       => $request->year,
                'status_id'  => $request->status_id,
                'id_plan'    => $request->id_plan,
                'segment_id' => $request->segment_id,
                'data'       => $request->input('data'),
                'id_user'    => $id_user,
            ]);

            $data->load(['plan', 'segment', 'status', 'user']);

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
        $message = "Error al actualizar precio de producto";
        $action = "Actualizar precio de producto";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $record = ProductPrice::findOrFail($id);

            $request->validate([
                'month'      => 'required|integer|min:1|max:12',
                'year'       => 'required|integer|digits:4',
                'status_id'  => 'required|in:1,2',
                'id_plan'    => 'required|exists:plans,id',
                'segment_id' => 'required|exists:segments,id',
                'data'       => 'required|array',
            ]);

            $record->update([
                'month'      => $request->month,
                'year'       => $request->year,
                'status_id'  => $request->status_id,
                'id_plan'    => $request->id_plan,
                'segment_id' => $request->segment_id,
                'data'       => $request->input('data'),
                'id_user'    => $id_user,
            ]);

            $data = $record->fresh(['plan', 'segment', 'status', 'user']);

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
        $message = "Error al cambiar estado de precio de producto";
        $action = "Cambiar estado de precio de producto";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $record = ProductPrice::findOrFail($id);

            $request->validate([
                'status_id' => 'required|in:1,2',
            ]);

            if ($request->status_id == 1) {
                if (empty($record->month) || empty($record->year) || empty($record->data) || empty($record->id_plan) || empty($record->segment_id)) {
                    return response([
                        "message" => "No se puede publicar el registro. Todos los campos deben estar completos (month, year, data, plan y segment)."
                    ], 400);
                }
            }

            $record->update(['status_id' => $request->status_id]);

            $data = $record->fresh(['plan', 'segment', 'status', 'user']);

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
        $message = "Error al eliminar precio de producto";
        $action = "Eliminar precio de producto";
        $id_user = Auth::user()->id ?? null;

        try {
            $record = ProductPrice::findOrFail($id);
            $record->delete();

            Audith::new($id_user, $action, $request->all(), 200, ['deleted_id' => $id]);

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(["message" => "Precio de producto eliminado correctamente"]);
    }
}
