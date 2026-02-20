<?php

namespace App\Http\Controllers;

use App\Jobs\SendMassEmail;
use App\Mail\MassNotification;
use App\Models\AgriculturalInputOutputRelationship;
use App\Models\MainCropsBuyingSellingTrafficLight;
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
use App\Models\HarvestPrices;
use App\Models\LivestockInputOutputRatio;
use App\Models\PitIndicator;
use App\Models\ProductPrice;
use App\Models\MarketGeneralControl;
use App\Models\StatusReport;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    public function statusReport(Request $request)
    {
        $message = "Error al obtener los estados de reportes";
        $action = "Listado de estados de reportes";
        $data = null;
        $id_user = Auth::user()->id ?? null;
        try {
            $data = StatusReport::get();

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

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
            // Verificar que el mes/año esté publicado en control general de mercado
            $control = MarketGeneralControl::where('month', $month)
                ->where('year', $year)
                ->where('status_id', 1)
                ->first();

            if (!$control) {
                $response = [
                    'message' => 'No hay datos publicados para el mes seleccionado.',
                    'error_code' => 600
                ];
                Audith::new($id_user, $action, $request->all(), 422, $response);
                return response()->json($response, 422);
            }

            $filters = function ($query) use ($id_plan, $month, $year) {
                $query->where('status_id', 1)
                    ->whereYear('date', $year)
                    ->whereMonth('date', $month)
                    ->where('id_plan', '<=', $id_plan);
            };

            // Filtro especial para mag_lease_index: obtener el mes buscado y los 2 meses anteriores
            // Calcular el rango de fechas: 2 meses atrás hasta el mes buscado
            $searchDate = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth();
            $twoMonthsBefore = \Carbon\Carbon::createFromDate($year, $month, 1)->subMonths(2)->startOfMonth();

            $mag_lease_data = DB::table('mag_lease_index')
                ->select('*')
                ->where('date', '>=', $twoMonthsBefore->format('Y-m-d'))
                ->where('date', '<=', $searchDate->format('Y-m-d'))
                ->where('id_plan', '<=', $id_plan)
                ->whereNull('deleted_at')
                ->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get()
                ->unique(function ($item) {
                    // Agrupar por mes-año único
                    $date = \Carbon\Carbon::parse($item->date);
                    return $date->format('Y-m');
                })
                ->take(3)
                ->values();

            // Verificar si existe el mes buscado en los resultados
            $searchedMonthExists = $mag_lease_data->contains(function ($item) use ($year, $month) {
                $date = \Carbon\Carbon::parse($item->date);
                return $date->year == $year && $date->month == $month;
            });

            // Si el mes buscado no existe, devolver colección vacía
            if (!$searchedMonthExists) {
                $mag_lease_with_plan = collect([]);
            } else {
                // Cargar las relaciones manualmente
                $mag_lease_ids = $mag_lease_data->pluck('id')->toArray();
                $mag_lease_with_plan = MagLeaseIndex::whereIn('id', $mag_lease_ids)
                    ->with('plan')
                    ->orderBy('date', 'desc')
                    ->get();
            }

            // Filtro especial para mag_steer_index: obtener el mes buscado y los 2 meses anteriores
            $mag_steer_data = DB::table('mag_steer_index')
                ->select('*')
                ->where('date', '>=', $twoMonthsBefore->format('Y-m-d'))
                ->where('date', '<=', $searchDate->format('Y-m-d'))
                ->where('id_plan', '<=', $id_plan)
                ->whereNull('deleted_at')
                ->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get()
                ->unique(function ($item) {
                    // Agrupar por mes-año único
                    $date = \Carbon\Carbon::parse($item->date);
                    return $date->format('Y-m');
                })
                ->take(3)
                ->values();

            // Verificar si existe el mes buscado en los resultados
            $searchedMonthExistsSteer = $mag_steer_data->contains(function ($item) use ($year, $month) {
                $date = \Carbon\Carbon::parse($item->date);
                return $date->year == $year && $date->month == $month;
            });

            // Si el mes buscado no existe, devolver colección vacía
            if (!$searchedMonthExistsSteer) {
                $mag_steer_with_plan = collect([]);
            } else {
                // Cargar las relaciones manualmente
                $mag_steer_ids = $mag_steer_data->pluck('id')->toArray();
                $mag_steer_with_plan = MagSteerIndex::whereIn('id', $mag_steer_ids)
                    ->with('plan')
                    ->orderBy('date', 'desc')
                    ->get();
            }

            // Filtro para tablas que usan columnas 'month' y 'year' directamente
            $filtersMonthYear = function ($query) use ($id_plan, $month, $year) {
                $query->where('status_id', 1)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->where('id_plan', '<=', $id_plan);
            };

            // Realizar las consultas a todas las tablas
            $data = [
                'news' => News::where($filters)->with('plan')->get(),
                'major_crops' => MajorCrop::where($filtersMonthYear)->with('plan')->get(),
                'mag_lease_index' => $mag_lease_with_plan,
                'mag_steer_index' => $mag_steer_with_plan,
                'insights' => Insight::where($filters)->with('plan')->get(),
                'price_main_active_ingredients_producers' => PriceMainActiveIngredientsProducer::where($filtersMonthYear)->with(['plan', 'segment'])->get(),
                'producer_segment_prices' => ProducerSegmentPrice::where($filtersMonthYear)->with('plan')->get(),
                'rainfall_records_provinces' => RainfallRecordProvince::where($filtersMonthYear)->with('plan')->get(),
                'main_grain_prices' => MainGrainPrice::where($filtersMonthYear)->with('plan')->get(),
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

            function getDataOrNull($modelClass, $filters, $with = [])
            {
                $query = $modelClass::where($filters);
                if (!empty($with)) {
                    $query->with($with);
                }
                $results = $query->get();
                return $results->isEmpty() ? null : $results;
            }

            // Consultas a las nuevas tablas
            $data = [
                'pit_indicators' => getDataOrNull(PitIndicator::class, $filters),
                'livestock_input_output_ratios' => getDataOrNull(LivestockInputOutputRatio::class, $filters, ['regionData']),
                'agricultural_input_output_relationships' => getDataOrNull(AgriculturalInputOutputRelationship::class, $filters, ['regionData']),
                'gross_margins_trend' => getDataOrNull(GrossMarginsTrend::class, $filters),
                'harvest_prices' => getDataOrNull(HarvestPrices::class, $filters),
                'product_prices' => getDataOrNull(ProductPrice::class, $filters, ['segment']),
                'gross_margins' => getDataOrNull(GrossMargin::class, $filters),
                'main_crops_buying_selling_traffic_light' => getDataOrNull(MainCropsBuyingSellingTrafficLight::class, $filters, ['inputs']),
            ];


            // Verificar si todos los arrays están vacíos
            $allEmpty = collect($data)->every(function ($items) {
                return is_null($items) || $items->isEmpty();
            });

            $trafficLights = $data['main_crops_buying_selling_traffic_light'];

            $transformed = [];

            if ($trafficLights) {
                foreach ($trafficLights as $item) {
                    $inputName = $item->inputs->name;
                    $variable = $item->variable;
                    $cultivos = $item->data;

                    foreach ($cultivos as $cultivo => $valor) {
                        if (!isset($transformed[$cultivo])) {
                            $transformed[$cultivo] = [];
                        }
                        if (!isset($transformed[$cultivo][$inputName])) {
                            $transformed[$cultivo][$inputName] = [];
                        }

                        $transformed[$cultivo][$inputName][$variable] = $valor;
                    }
                }
            } else {
                $transformed = null;
            }

            $data['main_crops_buying_selling_traffic_light'] = $transformed;


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

    public function deleteReports(Request $request)
    {
        Log::info('holaaaaaaaa');
        // Validar parámetros de mes y año
        $validator = Validator::make($request->all(), [
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|digits:4'
        ]);

        $action = "Eliminar reportes";
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

        $month = $request->input('month');
        $year = $request->input('year');

        try {
            DB::beginTransaction(); // Iniciar una transacción para garantizar integridad

            // Definir las tablas a eliminar datos
            $tables = [
                'news' => News::class,
                'major_crops' => MajorCrop::class,
                'mag_lease_index' => MagLeaseIndex::class,
                'mag_steer_index' => MagSteerIndex::class,
                'insights' => Insight::class,
                'price_main_active_ingredients_producers' => PriceMainActiveIngredientsProducer::class,
                'producer_segment_prices' => ProducerSegmentPrice::class,
                'rainfall_records_provinces' => RainfallRecordProvince::class,
                'main_grain_prices' => MainGrainPrice::class,
            ];

            $deletedCounts = [];

            foreach ($tables as $key => $model) {
                $deletedCounts[$key] = $model::whereYear('date', $year)
                    ->whereMonth('date', $month)
                    ->delete();
            }

            DB::commit(); // Confirmar la transacción

            // Verificar si se eliminaron registros
            if (array_sum($deletedCounts) === 0) {
                $response = [
                    'message' => 'No se encontraron registros para eliminar en el mes y año especificados.',
                    'error_code' => 601
                ];
                Audith::new($id_user, $action, $request->all(), 422, $response);
                return response()->json($response, 422);
            }

            // Registrar acción exitosa en auditoría
            $response = [
                'message' => 'Registros eliminados exitosamente.',
                'deleted_records' => $deletedCounts
            ];
            Audith::new($id_user, $action, $request->all(), 200, $response);

        } catch (Exception $e) {
            DB::rollBack(); // Revertir cambios en caso de error
            $response = ["message" => "Error al eliminar reportes", "error" => $e->getMessage(), "line" => $e->getLine()];
            Log::debug($response);
            Audith::new($id_user, $action, $request->all(), 500, $response);
            return response()->json($response, 500);
        }

        return response()->json($response, 200);
    }

    public function deleteBusinessIndicators(Request $request)
    {
        // Validar parámetros de mes y año
        $validator = Validator::make($request->all(), [
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|digits:4'
        ]);

        $action = "Eliminar indicadores comerciales";
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

        $month = $request->input('month');
        $year = $request->input('year');

        try {
            DB::beginTransaction(); // Iniciar una transacción para garantizar integridad

            // Definir las tablas a eliminar datos
            $tables = [
                'pit_indicators' => PitIndicator::class,
                'livestock_input_output_ratios' => LivestockInputOutputRatio::class,
                'agricultural_input_output_relationships' => AgriculturalInputOutputRelationship::class,
                'gross_margins_trend' => GrossMarginsTrend::class,
                'harvest_prices' => HarvestPrices::class,
                'product_prices' => ProductPrice::class,
                'gross_margins' => GrossMargin::class,
                'main_crops_buying_selling_traffic_light' => MainCropsBuyingSellingTrafficLight::class,
            ];

            $deletedCounts = [];

            foreach ($tables as $key => $model) {
                $deletedCounts[$key] = $model::whereYear('date', $year)
                    ->whereMonth('date', $month)
                    ->delete();
            }

            DB::commit(); // Confirmar la transacción

            // Verificar si se eliminaron registros
            if (array_sum($deletedCounts) === 0) {
                $response = [
                    'message' => 'No se encontraron registros para eliminar en el mes y año especificados.',
                    'error_code' => 601
                ];
                Audith::new($id_user, $action, $request->all(), 422, $response);
                return response()->json($response, 422);
            }

            // Registrar acción exitosa en auditoría
            $response = [
                'message' => 'Registros eliminados exitosamente.',
                'deleted_records' => $deletedCounts
            ];
            Audith::new($id_user, $action, $request->all(), 200, $response);

        } catch (Exception $e) {
            DB::rollBack(); // Revertir cambios en caso de error
            $response = ["message" => "Error al eliminar indicadores comerciales", "error" => $e->getMessage(), "line" => $e->getLine()];
            Log::debug($response);
            Audith::new($id_user, $action, $request->all(), 500, $response);
            return response()->json($response, 500);
        }

        return response()->json($response, 200);
    }

}
