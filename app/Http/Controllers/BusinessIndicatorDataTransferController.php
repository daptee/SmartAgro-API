<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BusinessIndicatorControlController;
use App\Models\AgriculturalInputOutputRelationship;
use App\Models\BusinessIndicatorControl;
use App\Models\GrossMargin;
use App\Models\GrossMarginsTrend;
use App\Models\HarvestPrices;
use App\Models\LivestockInputOutputRatio;
use App\Models\MainCropsBuyingSellingTrafficLight;
use App\Models\PitIndicator;
use App\Models\ProductPrice;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BusinessIndicatorDataTransferController extends Controller
{
    /**
     * Todos los modelos usan columnas 'month' y 'year' directas.
     */
    private array $monthYearModels = [
        'pit_indicators'                         => PitIndicator::class,
        'gross_margin'                           => GrossMargin::class,
        'gross_margins_trend'                    => GrossMarginsTrend::class,
        'livestock_input_output_ratio'           => LivestockInputOutputRatio::class,
        'agricultural_input_output_relationship' => AgriculturalInputOutputRelationship::class,
        'products_prices'                        => ProductPrice::class,
        'harvest_prices'                         => HarvestPrices::class,
        'main_crops_buying_selling_traffic_light' => MainCropsBuyingSellingTrafficLight::class,
    ];

    /**
     * GET /export-business-indicator-data?month=1&year=2025
     * Exporta todos los datos de indicadores comerciales de un mes/año como archivo JSON descargable.
     */
    public function export(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year'  => 'required|integer|digits:4',
        ]);

        $month = (int) $request->month;
        $year  = (int) $request->year;

        try {
            $blocks = [];

            // business_indicator_controls
            $blocks['business_indicator_controls'] = BusinessIndicatorControl::where('month', $month)
                ->where('year', $year)
                ->get()
                ->toArray();

            // Todos los bloques usan month/year
            foreach ($this->monthYearModels as $key => $model) {
                $blocks[$key] = $model::where('month', $month)
                    ->where('year', $year)
                    ->get()
                    ->toArray();
            }

            $payload = [
                'month'       => $month,
                'year'        => $year,
                'exported_at' => now()->toISOString(),
                'blocks'      => $blocks,
            ];

            $filename = "business_indicator_data_{$year}_" . str_pad($month, 2, '0', STR_PAD_LEFT) . ".json";
            $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            return response()->streamDownload(function () use ($json) {
                echo $json;
            }, $filename, [
                'Content-Type' => 'application/json',
            ]);
        } catch (Exception $e) {
            Log::error('Error en exportación de datos de indicadores comerciales', [
                'error' => $e->getMessage(),
                'line'  => $e->getLine(),
            ]);
            return response()->json(['message' => 'Error al exportar los datos.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /import-business-indicator-data
     * Importa datos de indicadores comerciales desde un archivo JSON exportado previamente.
     * Por defecto solo inserta los bloques que NO tengan datos para ese mes/año.
     * Con overwrite=true reemplaza los datos existentes del mes/año (soft delete + insert),
     * lo que permite sincronizar en ambos sentidos (dev -> prod y prod -> dev).
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:json,txt',
            'overwrite' => 'sometimes|in:0,1,true,false',
        ]);

        try {
            $content = file_get_contents($request->file('file')->getRealPath());
            $data    = json_decode($content, true);

            if (!$data || !isset($data['month'], $data['year'], $data['blocks'])) {
                return response()->json(['message' => 'El archivo JSON no tiene el formato esperado.'], 422);
            }

            $month  = (int) $data['month'];
            $year   = (int) $data['year'];
            $blocks = $data['blocks'];
            $now    = now()->toDateTimeString();
            $results = [];
            $overwrite = $request->boolean('overwrite');

            DB::transaction(function () use ($blocks, $month, $year, $now, $overwrite, &$results) {
                // business_indicator_controls
                $results['business_indicator_controls'] = $this->importBlock(
                    'business_indicator_controls',
                    $blocks['business_indicator_controls'] ?? [],
                    BusinessIndicatorControl::class,
                    fn($m) => $m::where('month', $month)->where('year', $year),
                    $now,
                    $overwrite
                );

                // Todos los bloques usan month/year
                foreach ($this->monthYearModels as $key => $model) {
                    $results[$key] = $this->importBlock(
                        $key,
                        $blocks[$key] ?? [],
                        $model,
                        fn($m) => $m::where('month', $month)->where('year', $year),
                        $now,
                        $overwrite
                    );
                }

                // Recalcular y sincronizar el control general tras la importación
                $newData = BusinessIndicatorControlController::calculateBlockStatuses($month, $year);
                $control = BusinessIndicatorControl::firstOrCreate(
                    ['month' => $month, 'year' => $year],
                    ['status_id' => 2]
                );
                $updates = ['data' => $newData];
                if (!in_array(true, $newData, true)) {
                    $updates['status_id'] = 2;
                }
                $control->update($updates);
            });

            return response()->json([
                'message' => 'Importación completada.',
                'month'   => $month,
                'year'    => $year,
                'overwrite' => $overwrite,
                'results' => $results,
            ], 200);
        } catch (Exception $e) {
            Log::error('Error en importación de datos de indicadores comerciales', [
                'error' => $e->getMessage(),
                'line'  => $e->getLine(),
            ]);
            return response()->json(['message' => 'Error al importar los datos.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Importa un bloque de registros para el mes/año.
     * - Sin overwrite: solo inserta si no existen datos para el mes/año en la tabla.
     * - Con overwrite: elimina (soft delete) los datos existentes del mes/año y luego inserta.
     *
     * @param callable $scope Recibe la clase del modelo y devuelve el query filtrado por mes/año.
     */
    private function importBlock(string $key, array $records, string $model, callable $scope, string $now, bool $overwrite = false): string
    {
        if (empty($records)) {
            return 'skipped (no data in file)';
        }

        $removed = 0;
        if ($scope($model)->exists()) {
            if (!$overwrite) {
                return 'skipped (already exists)';
            }
            $removed = $scope($model)->delete();
        }

        $toInsert = array_map(function ($record) use ($now) {
            unset($record['id']);

            if (!empty($record['created_at'])) {
                $record['created_at'] = date('Y-m-d H:i:s', strtotime($record['created_at']));
            } else {
                $record['created_at'] = $now;
            }

            $record['updated_at'] = $now;

            foreach ($record as $k => $v) {
                if (is_array($v)) {
                    $record[$k] = json_encode($v);
                }
            }
            return $record;
        }, $records);

        // Descartar columnas que no existen en la tabla destino (los esquemas de dev y prod pueden diferir)
        $instance = new $model;
        $columns  = array_flip(Schema::connection($instance->getConnectionName())->getColumnListing($instance->getTable()));
        $ignored  = [];
        $toInsert = array_map(function ($record) use ($columns, &$ignored) {
            foreach (array_diff_key($record, $columns) as $column => $_) {
                $ignored[$column] = true;
            }
            return array_intersect_key($record, $columns);
        }, $toInsert);

        $model::insert($toInsert);

        $result = $removed > 0
            ? 'replaced (' . count($toInsert) . ' records imported, ' . $removed . ' removed)'
            : 'imported (' . count($toInsert) . ' records)';

        if (!empty($ignored)) {
            $result .= ' [ignored columns: ' . implode(', ', array_keys($ignored)) . ']';
        }

        return $result;
    }
}
