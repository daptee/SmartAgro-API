<?php

namespace App\Http\Controllers;

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
                      ->whereMonth('date', $month);

                if ($id_plan == 1) { // Usuario de plan gratuito
                    $query->where('id_plan', 1);
                } elseif ($id_plan == 2) { // Usuario de plan pago
                    $query->whereIn('id_plan', [1, 2]);
                }
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
}