<?php

namespace App\Http\Controllers;

use App\Models\HarvestPrices;
use App\Models\Audith;
use App\Http\Controllers\BusinessIndicatorControlController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class HarvestPricesController extends Controller
{
    // GET ALL
    public function index(Request $request)
    {
        $message = "Error al obtener precios de cosecha";
        $action = "Listado de precios de cosecha";
        $data = null;
        $meta = null;

        try {
            $perPage = $request->query('per_page');
            $page = $request->query('page', 1);

            $query = HarvestPrices::query();

            if ($request->has('month') && $request->month) {
                $query->where('month', (int)$request->month);
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

            $query->orderBy('year', 'desc')->orderByRaw('CAST(month AS UNSIGNED) DESC');

            if (is_null($perPage)) {
                $data = $query->with(['plan', 'status', 'user'])->get();
            } else {
                $records = $query->with(['plan', 'status', 'user'])->paginate($perPage, ['*'], 'page', $page);
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
        $message = "Error al crear precio de cosecha";
        $action = "Crear precio de cosecha";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $request->validate([
                'month'     => 'required|integer|min:1|max:12',
                'year'      => 'required|integer|digits:4',
                'status_id' => 'required|in:1,2',
                'id_plan'   => 'required|exists:plans,id',
                'data'      => 'required|array',
            ]);

            $data = HarvestPrices::create([
                'month'     => (int)$request->month,
                'year'      => $request->year,
                'status_id' => $request->status_id,
                'id_plan'   => $request->id_plan,
                'data'      => $request->input('data'),
                'id_user'   => $id_user,
            ]);

            $data->load(['plan', 'status', 'user']);
            BusinessIndicatorControlController::syncBlockStatus((int)$request->month, $request->year, 'harvest_prices', $request->status_id == 1);

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
        $message = "Error al actualizar precio de cosecha";
        $action = "Actualizar precio de cosecha";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $record = HarvestPrices::findOrFail($id);

            $request->validate([
                'month'     => 'required|integer|min:1|max:12',
                'year'      => 'required|integer|digits:4',
                'status_id' => 'required|in:1,2',
                'id_plan'   => 'required|exists:plans,id',
                'data'      => 'required|array',
            ]);

            $record->update([
                'month'     => (int)$request->month,
                'year'      => $request->year,
                'status_id' => $request->status_id,
                'id_plan'   => $request->id_plan,
                'data'      => $request->input('data'),
                'id_user'   => $id_user,
            ]);

            $data = $record->fresh(['plan', 'status', 'user']);
            BusinessIndicatorControlController::syncBlockStatus((int)$request->month, $request->year, 'harvest_prices', $request->status_id == 1);

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
        $message = "Error al cambiar estado de precio de cosecha";
        $action = "Cambiar estado de precio de cosecha";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $record = HarvestPrices::findOrFail($id);

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
            BusinessIndicatorControlController::syncBlockStatus($data->month, $data->year, 'harvest_prices', $request->status_id == 1);

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
        $message = "Error al eliminar precio de cosecha";
        $action = "Eliminar precio de cosecha";
        $id_user = Auth::user()->id ?? null;

        try {
            $record = HarvestPrices::findOrFail($id);
            $record->delete();

            Audith::new($id_user, $action, $request->all(), 200, ['deleted_id' => $id]);

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(["message" => "Precio de cosecha eliminado correctamente"]);
    }

    // DELETE DUPLICATES
    public function deleteDuplicates(Request $request)
    {
        $message = "Error al eliminar duplicados";
        $action = "Eliminar duplicados de precios de cosecha";
        $id_user = Auth::user()->id ?? null;
        $deleted = 0;

        try {
            $groups = HarvestPrices::selectRaw('region, year, month')
                ->groupBy('region', 'year', 'month')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($groups as $group) {
                $records = HarvestPrices::whereRaw('region <=> ?', [$group->region])
                    ->where('year', $group->year)
                    ->where('month', $group->month)
                    ->orderBy('id_plan', 'desc')
                    ->orderBy('id', 'desc')
                    ->get();

                foreach ($records->skip(1) as $duplicate) {
                    $duplicate->forceDelete();
                    $deleted++;
                }
            }

            Audith::new($id_user, $action, $request->all(), 200, ['deleted' => $deleted]);

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(["message" => "Duplicados eliminados correctamente", "deleted" => $deleted]);
    }
}
