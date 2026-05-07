<?php

namespace App\Http\Controllers;

use App\Models\AgriculturalInputOutputRelationship;
use App\Models\BusinessIndicatorControl;
use App\Models\Audith;
use App\Models\GrossMargin;
use App\Models\GrossMarginsTrend;
use App\Models\HarvestPrices;
use App\Models\LivestockInputOutputRatio;
use App\Models\MainCropsBuyingSellingTrafficLight;
use App\Models\PitIndicator;
use App\Models\ProductPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class BusinessIndicatorControlController extends Controller
{
    // GET ALL
    public function index(Request $request)
    {
        $message = "Error al obtener controles de indicadores comerciales";
        $action = "Listado de controles de indicadores comerciales";
        $data = null;
        $meta = null;

        try {
            $perPage = $request->query('per_page');
            $page = $request->query('page', 1);

            $query = BusinessIndicatorControl::query();

            if ($request->has('month') && $request->month) {
                $query->where('month', (int)$request->month);
            }

            if ($request->has('year') && $request->year) {
                $query->where('year', $request->year);
            }

            // Filtro rango de fechas (por año+mes)
            if ($request->has('date_from') && $request->date_from) {
                [$fy, $fm] = explode('-', $request->date_from);
                $query->where(function ($q) use ($fy, $fm) {
                    $q->where('year', '>', $fy)
                      ->orWhere(function ($q2) use ($fy, $fm) {
                          $q2->where('year', $fy)->where('month', '>=', $fm);
                      });
                });
            }

            if ($request->has('date_to') && $request->date_to) {
                [$ty, $tm] = explode('-', $request->date_to);
                $query->where(function ($q) use ($ty, $tm) {
                    $q->where('year', '<', $ty)
                      ->orWhere(function ($q2) use ($ty, $tm) {
                          $q2->where('year', $ty)->where('month', '<=', $tm);
                      });
                });
            }

            if ($request->has('status_id') && $request->status_id) {
                $query->where('status_id', $request->status_id);
            }

            $query->orderBy('year', 'desc')->orderByRaw('CAST(month AS UNSIGNED) DESC');

            // Recalcular estado de cada módulo desde sus tablas y sincronizar el JSON data
            BusinessIndicatorControl::get()->each(function ($control) {
                $realData = self::calculateBlockStatuses($control->month, $control->year);
                if ($realData !== ($control->data ?? [])) {
                    $control->update(['data' => $realData]);
                }
            });

            if (is_null($perPage)) {
                $data = $query->with(['status', 'user'])->get();
            } else {
                $records = $query->with(['status', 'user'])->paginate($perPage, ['*'], 'page', $page);
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

    // GET BY ID
    public function show(Request $request, $id)
    {
        $message = "Error al obtener control de indicadores comerciales";
        $action = "Detalle de control de indicadores comerciales";
        $data = null;

        try {
            $data = BusinessIndicatorControl::with(['status', 'user'])->findOrFail($id);

            Audith::new(Auth::user()->id ?? null, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new(Auth::user()->id ?? null, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // POST
    public function store(Request $request)
    {
        $message = "Error al crear control de indicadores comerciales";
        $action = "Crear control de indicadores comerciales";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $request->validate([
                'month'           => 'required|integer|min:1|max:12',
                'year'            => 'required|integer|min:2000|max:2100',
                'additional_info' => 'nullable|array',
            ]);

            $existing = BusinessIndicatorControl::withTrashed()
                ->where('year', $request->year)
                ->where('month', (int)$request->month)
                ->first();

            if ($existing && !$existing->trashed()) {
                return response([
                    "message" => "Ya existe un registro para el mes {$request->month} del año {$request->year}."
                ], 400);
            }

            $initialData = self::calculateBlockStatuses((int)$request->month, $request->year);

            if ($existing && $existing->trashed()) {
                $existing->restore();
                $existing->update([
                    'data'            => $initialData,
                    'additional_info' => $request->input('additional_info'),
                    'status_id'       => 2,
                    'id_user'         => $id_user,
                ]);
                $data = $existing;
            } else {
                $data = BusinessIndicatorControl::create([
                    'month'           => (int)$request->month,
                    'year'            => $request->year,
                    'data'            => $initialData,
                    'additional_info' => $request->input('additional_info'),
                    'status_id'       => 2,
                    'id_user'         => $id_user,
                ]);
            }

            $data->load(['status', 'user']);

            Audith::new($id_user, $action, $request->all(), 201, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"), 201);
    }

    // PUT - Editar mes y año
    public function update(Request $request, $id)
    {
        $message = "Error al actualizar control de indicadores comerciales";
        $action = "Actualizar control de indicadores comerciales";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $control = BusinessIndicatorControl::findOrFail($id);

            $request->validate([
                'month'           => 'required|integer|min:1|max:12',
                'year'            => 'required|integer|min:2000|max:2100',
                'additional_info' => 'nullable|array',
            ]);

            $exists = BusinessIndicatorControl::where('year', $request->year)
                ->where('month', (int)$request->month)
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return response([
                    "message" => "Ya existe otro registro para el mes {$request->month} del año {$request->year}."
                ], 400);
            }

            $newData = self::calculateBlockStatuses((int)$request->month, $request->year);

            $control->update([
                'month'           => (int)$request->month,
                'year'            => $request->year,
                'data'            => $newData,
                'additional_info' => $request->input('additional_info'),
                'id_user'         => $id_user,
            ]);

            $data = $control->fresh(['status', 'user']);

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // PUT - Actualizar campo específico del JSON data
    public function updateData(Request $request, $id)
    {
        $message = "Error al actualizar datos del control de indicadores comerciales";
        $action = "Actualizar datos del control de indicadores comerciales";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $control = BusinessIndicatorControl::findOrFail($id);

            $request->validate([
                'block'  => 'required|string|in:pit_indicators,gross_margin,gross_margins_trend,livestock_input_output_ratio,agricultural_input_output_relationship,products_prices,harvest_prices,main_crops_buying_selling_traffic_light',
                'loaded' => 'required|boolean',
            ]);

            $currentData = $control->data ?? [];
            $currentData[$request->block] = $request->loaded;

            $control->update([
                'data'    => $currentData,
                'id_user' => $id_user,
            ]);

            // Sincronizar status de los registros del módulo según loaded
            $blockModelMap = [
                'pit_indicators'                          => PitIndicator::class,
                'gross_margin'                            => GrossMargin::class,
                'gross_margins_trend'                     => GrossMarginsTrend::class,
                'livestock_input_output_ratio'            => LivestockInputOutputRatio::class,
                'agricultural_input_output_relationship'  => AgriculturalInputOutputRelationship::class,
                'products_prices'                         => ProductPrice::class,
                'harvest_prices'                          => HarvestPrices::class,
                'main_crops_buying_selling_traffic_light' => MainCropsBuyingSellingTrafficLight::class,
            ];

            if (isset($blockModelMap[$request->block])) {
                $newStatus = $request->loaded ? 1 : 2;
                $currentStatus = $request->loaded ? 2 : 1;

                $blockModelMap[$request->block]::where('month', $control->month)
                    ->where('year', $control->year)
                    ->where('status_id', $currentStatus)
                    ->update(['status_id' => $newStatus]);
            }

            $data = $control->fresh(['status', 'user']);

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
        $message = "Error al cambiar estado del control de indicadores comerciales";
        $action = "Cambiar estado del control de indicadores comerciales";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $control = BusinessIndicatorControl::findOrFail($id);

            $request->validate([
                'status_id' => 'required|in:1,2',
            ]);

            $control->update(['status_id' => $request->status_id]);

            $data = $control->fresh(['status', 'user']);

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
        $message = "Error al eliminar control de indicadores comerciales";
        $action = "Eliminar control de indicadores comerciales";
        $id_user = Auth::user()->id ?? null;

        try {
            $control = BusinessIndicatorControl::findOrFail($id);
            $control->delete();

            Audith::new($id_user, $action, $request->all(), 200, ['deleted_id' => $id]);

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(["message" => "Control de indicadores comerciales eliminado correctamente"]);
    }

    // PUT - Replicar additional_info a todos los meses del año
    public function replicateAdditionalInfo(Request $request)
    {
        $message = "Error al replicar additional_info";
        $action  = "Replicar additional_info de indicadores comerciales";
        $id_user = Auth::user()->id ?? null;

        try {
            $request->validate([
                'month' => 'required|integer|min:1|max:12',
                'year'  => 'required|integer|min:2000|max:2100',
            ]);

            $month = (int) $request->month;
            $year  = (int) $request->year;

            $blockModelMap = [
                'pit_indicators'                          => PitIndicator::class,
                'gross_margin'                            => GrossMargin::class,
                'gross_margins_trend'                     => GrossMarginsTrend::class,
                'livestock_input_output_ratio'            => LivestockInputOutputRatio::class,
                'agricultural_input_output_relationship'  => AgriculturalInputOutputRelationship::class,
                'products_prices'                         => ProductPrice::class,
                'harvest_prices'                          => HarvestPrices::class,
                'main_crops_buying_selling_traffic_light' => MainCropsBuyingSellingTrafficLight::class,
            ];

            $results = [];

            foreach ($blockModelMap as $block => $modelClass) {
                $reference = $modelClass::where('status_id', 1)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->first();

                if (!$reference || is_null($reference->additional_info)) {
                    $results[$block] = 0;
                    continue;
                }

                $results[$block] = $modelClass::where(function ($q) use ($month, $year) {
                    $q->where('month', '!=', $month)->orWhere('year', '!=', $year);
                })->update(['additional_info' => json_encode($reference->additional_info)]);
            }

            Audith::new($id_user, $action, $request->all(), 200, $results);

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(["data" => $results]);
    }

    /**
     * Calcula el estado real de cada bloque para un mes/año dado
     */
    public static function calculateBlockStatuses($month, $year): array
    {
        return [
            'pit_indicators'                      => PitIndicator::where('month', $month)->where('year', $year)->where('status_id', 1)->whereNull('deleted_at')->exists(),
            'gross_margin'                        => GrossMargin::where('month', $month)->where('year', $year)->where('status_id', 1)->whereNull('deleted_at')->exists(),
            'gross_margins_trend'                 => GrossMarginsTrend::where('month', $month)->where('year', $year)->where('status_id', 1)->whereNull('deleted_at')->exists(),
            'livestock_input_output_ratio'        => LivestockInputOutputRatio::where('month', $month)->where('year', $year)->where('status_id', 1)->whereNull('deleted_at')->exists(),
            'agricultural_input_output_relationship' => AgriculturalInputOutputRelationship::where('month', $month)->where('year', $year)->where('status_id', 1)->whereNull('deleted_at')->exists(),
            'products_prices'                     => ProductPrice::where('month', $month)->where('year', $year)->where('status_id', 1)->whereNull('deleted_at')->exists(),
            'harvest_prices'                      => HarvestPrices::where('month', $month)->where('year', $year)->where('status_id', 1)->whereNull('deleted_at')->exists(),
            'main_crops_buying_selling_traffic_light' => MainCropsBuyingSellingTrafficLight::where('month', $month)->where('year', $year)->where('status_id', 1)->whereNull('deleted_at')->exists(),
        ];
    }

    /**
     * Helper estático para sincronizar el JSON data cuando un bloque cambia de estado
     */
    public static function syncBlockStatus($month, $year, $blockName, $isPublished): void
    {
        $control = BusinessIndicatorControl::firstOrCreate(
            ['month' => $month, 'year' => $year],
            ['status_id' => 2]
        );

        // Recalcular todos los bloques desde BD y forzar el bloque actual
        $newData = self::calculateBlockStatuses($month, $year);
        $newData[$blockName] = $isPublished;

        $updates = ['data' => $newData];

        // Solo forzar borrador si ningún bloque está publicado
        if (!in_array(true, $newData, true)) {
            $updates['status_id'] = 2;
        }

        $control->update($updates);
    }
}
