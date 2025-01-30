<?php

namespace App\Http\Controllers;

use App\Models\AgriculturalInputOutputRelationship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\News;
use App\Models\MajorCrop;
use App\Models\MagLeaseIndex;
use App\Models\MagSteerIndex;
use App\Models\Insight;
use App\Models\PriceMainActiveIngredientsProducer;
use App\Models\ProducerSegmentPrice;
use App\Models\RainfallRecordProvince;
use App\Models\MainGrainPrice;
use App\Models\Audith;
use App\Models\GrossMargin;
use App\Models\GrossMarginsTrend;
use App\Models\GrossMarginsTrend2;
use App\Models\LivestockInputOutputRatio;
use App\Models\PitIndicator;
use App\Models\ProductPrice;
use Exception;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    public function reports(Request $request)
    {
        // Validar parámetros de mes y año
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|digits:4'
        ]);

        $message = "Error al obtener reportes";
        $action = "Listado de reportes";
        $id_user = Auth::user()->id ?? null;
        $id_plan = Auth::user()->id_plan ?? null;

        $month = $request->input('month');
        $year = $request->input('year');

        try {
            $filters = function($query) use ($id_plan, $month, $year) {
                $query->whereYear('date', $year)
                      ->whereMonth('date', $month)
                      ->where('id_plan', '<=', $id_plan);
            };

            // Realizar las consultas a todas las tablas
            $data = [
                'news' => News::where($filters)->with('plan')->get(),
                'major_crops' => MajorCrop::where($filters)->with('plan')->get(),
                'mag_lease_index' => MagLeaseIndex::where($filters)->with('plan')->get(),
                'mag_steer_index' => MagSteerIndex::where($filters)->with('plan')->get(),
                'insights' => Insight::where($filters)->with('plan')->get(),
                'price_main_active_ingredients_producers' => PriceMainActiveIngredientsProducer::where($filters)->with('plan')->get(),
                'producer_segment_prices' => ProducerSegmentPrice::where($filters)->with('plan')->get(),
                'rainfall_records_provinces' => RainfallRecordProvince::where($filters)->with('plan')->get(),
                'main_grain_prices' => MainGrainPrice::where($filters)->with('plan')->get(),
            ];

            // Verificar si todos los arrays están vacíos
            $allEmpty = collect($data)->every(function ($items) {
                return $items->isEmpty();
            });

            if ($allEmpty) {
                return response()->json([
                    'message' => 'No hay datos para el mes seleccionado. Por favor, cambie el mes de filtro.',
                    'error_code' => 600
                ], 422);
            }

            // Registrar acción exitosa en auditoría
            Audith::new($id_user, $action, null, 200, null);
        } catch (Exception $e) {
            // Manejo de errores
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            Audith::new($id_user, $action, null, 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response()->json(['data' => $data], 200);
    }

    public function business_indicators(Request $request)
    {
        // Validar parámetros de mes y año
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|digits:4'
        ]);

        $message = "Error al obtener indicadores comerciales";
        $action = "Listado de indicadores comerciales";
        $id_user = Auth::user()->id ?? null;
        $id_plan = Auth::user()->id_plan ?? null;

        $month = $request->input('month');
        $year = $request->input('year');

        try {
            $filters = function ($query) use ($id_plan, $month, $year) {
                $query->whereYear('date', $year)
                    ->whereMonth('date', $month)
                    ->where('id_plan', '<=', $id_plan);
            };

            // Consultas a las nuevas tablas
            $data = [
                'pit_indicators' => PitIndicator::where($filters)->with('plan')->get(),
                'livestock_input_output_ratios' => LivestockInputOutputRatio::where($filters)->with('plan')->get(),
                'agricultural_input_output_relationships' => AgriculturalInputOutputRelationship::where($filters)->with('plan')->get(),
                'gross_margins_trend' => GrossMarginsTrend::where($filters)->with('plan')->get(),
                'gross_margins_trend_2' => GrossMarginsTrend2::where($filters)->with('plan')->get(),
                'product_prices' => ProductPrice::where($filters)->with('plan')->get(),
                'gross_margins' => GrossMargin::where($filters)->with('plan')->get(),
            ];

            // Verificar si todos los arrays están vacíos
            $allEmpty = collect($data)->every(function ($items) {
                return $items->isEmpty();
            });

            if ($allEmpty) {
                return response()->json([
                    'message' => 'No hay datos para el mes seleccionado. Por favor, cambie el mes de filtro.',
                    'error_code' => 600
                ], 422);
            }

            // Registrar acción exitosa en auditoría
            Audith::new($id_user, $action, null, 200, null);
        } catch (Exception $e) {
            // Manejo de errores
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            Audith::new($id_user, $action, null, 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response()->json(['data' => $data], 200);
    }
}