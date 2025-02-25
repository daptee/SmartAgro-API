<?php

namespace App\Http\Controllers;

use App\Jobs\SendMassEmail;
use App\Mail\MassNotification;
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
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    public function reports(Request $request)
    {
        // Validar parámetros de mes y año
        $validator = Validator::make($request->all(), [
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|digits:4'
        ]);

        $action = "Listado de reportes";
        $status = 422;
        $id_user = Auth::user()->id ?? null;

        if ($validator->fails()) {
            $response = [
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ];
            Audith::new($id_user, $action, $request->all(), $status, $response);
            return response()->json($response, $status);
        }

        $message = "Error al obtener reportes";
        $id_plan = Auth::user()->id_plan ?? null;

        $month = $request->input('month');
        $year = $request->input('year');

        try {
            $filters = function ($query) use ($id_plan, $month, $year) {
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
                $response = [
                    'message' => 'No hay datos para el mes seleccionado. Por favor, cambie el mes de filtro.',
                    'error_code' => 600
                ];
                Audith::new($id_user, $action, $request->all(), 422, $response);
                return response()->json($response, 422);
            }

            // Registrar acción exitosa en auditoría
            Audith::new($id_user, $action, $request->all(), 200, ['data' => $data]);
        } catch (Exception $e) {
            // Manejo de errores
            $response = ["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()];
            Log::debug($response);
            Audith::new($id_user, $action, $request->all(), 500, $response);
            return response()->json($response, 500);
        }

        return response()->json(['data' => $data], 200);
    }

    public function business_indicators(Request $request)
    {
        // Validar parámetros de mes y año
        $validator = Validator::make($request->all(), [
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|digits:4'
        ]);

        $action = "Listado de indicadores comerciales";
        $status = 422;
        $id_user = Auth::user()->id ?? null;

        if ($validator->fails()) {
            $response = [
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ];
            Audith::new($id_user, $action, $request->all(), $status, $response);
            return response()->json($response, $status);
        }

        $message = "Error al obtener indicadores comerciales";
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
                $response = [
                    'message' => 'No hay datos para el mes seleccionado. Por favor, cambie el mes de filtro.',
                    'error_code' => 600
                ];
                Audith::new($id_user, $action, $request->all(), 422, $response);
                return response()->json($response, 422);
            }

            // Registrar acción exitosa en auditoría
            Audith::new($id_user, $action, $request->all(), 200, ['data' => $data]);
        } catch (Exception $e) {
            // Manejo de errores
            $response = ["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()];
            Log::debug($response);
            Audith::new($id_user, $action, $request->all(), 500, $response);
            return response()->json($response, 500);
        }

        return response()->json(['data' => $data], 200);
    }

    public function notification_users_report()
    {
        if (config('services.app_environment') == 'DEV') {
            $users = User::whereIn('email', [
                'slarramendy@daptee.com.ar'
            ])->pluck('email')->toArray();
        } else {
            $users = User::pluck('email')->toArray();
        }

        if (empty($users)) {
            return response()->json(['message' => 'No hay destinatarios'], 400);
        }

        try {
            Mail::to('enzo100amarilla@gmail.com') // Dirección de "envío principal"
                ->bcc($users) // Todos los demás en BCC
                ->send(new MassNotification());

            Log::info("Correo enviado a múltiples destinatarios en BCC");

            return response()->json(['message' => 'Correos enviados']);
        } catch (\Exception $e) {
            Log::error("Error enviando correo: " . $e->getMessage());
            return response()->json(['message' => 'Error enviando correos'], 500);
        }
    }
}
