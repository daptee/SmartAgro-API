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
use Illuminate\Support\Facades\Log;

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
     * Solo inserta los bloques que NO tengan datos para ese mes/año.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:json',
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

            // business_indicator_controls
            $results['business_indicator_controls'] = $this->importBlock(
                'business_indicator_controls',
                $blocks['business_indicator_controls'] ?? [],
                BusinessIndicatorControl::class,
                fn($m) => $m::where('month', $month)->where('year', $year)->exists(),
                $now
            );

            // Todos los bloques usan month/year
            foreach ($this->monthYearModels as $key => $model) {
                $results[$key] = $this->importBlock(
                    $key,
                    $blocks[$key] ?? [],
                    $model,
                    fn($m) => $m::where('month', $month)->where('year', $year)->exists(),
                    $now
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

            return response()->json([
                'message' => 'Importación completada.',
                'month'   => $month,
                'year'    => $year,
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
     * Importa un bloque de registros si no existen datos para el mes/año en la tabla.
     */
    private function importBlock(string $key, array $records, string $model, callable $existsCheck, string $now): string
    {
        if (empty($records)) {
            return 'skipped (no data in file)';
        }

        if ($existsCheck($model)) {
            return 'skipped (already exists)';
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

        $model::insert($toInsert);

        return 'imported (' . count($toInsert) . ' records)';
    }
}
