<?php

namespace App\Http\Controllers;

use App\Models\Insight;
use App\Models\MagLeaseIndex;
use App\Models\MagSteerIndex;
use App\Models\MainGrainPrice;
use App\Models\MajorCrop;
use App\Models\MarketGeneralControl;
use App\Models\News;
use App\Models\PriceMainActiveIngredientsProducer;
use App\Models\ProducerSegmentPrice;
use App\Models\RainfallRecordProvince;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MarketDataTransferController extends Controller
{
    /**
     * Modelos que usan columna 'date' para filtrar por mes/año.
     * Modelos que usan columnas 'month' y 'year' directas.
     */
    private array $dateModels = [
        'insights'    => Insight::class,
        'news'        => News::class,
        'mag_lease_index' => MagLeaseIndex::class,
        'mag_steer_index' => MagSteerIndex::class,
    ];

    private array $monthYearModels = [
        'major_crops'                            => MajorCrop::class,
        'main_grain_prices'                      => MainGrainPrice::class,
        'rainfall_record_provinces'              => RainfallRecordProvince::class,
        'producer_segment_prices'                => ProducerSegmentPrice::class,
        'prices_main_active_ingredients_producers' => PriceMainActiveIngredientsProducer::class,
    ];

    /**
     * GET /export-market-data?month=1&year=2025
     * Exporta todos los datos de mercado de un mes/año como archivo JSON descargable.
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

            // market_general_controls (solo usa month/year)
            $blocks['market_general_controls'] = MarketGeneralControl::where('month', $month)
                ->where('year', $year)
                ->get()
                ->toArray();

            // Modelos con columna 'date'
            foreach ($this->dateModels as $key => $model) {
                $blocks[$key] = $model::whereMonth('date', $month)
                    ->whereYear('date', $year)
                    ->get()
                    ->toArray();
            }

            // Modelos con columnas 'month' y 'year'
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

            $filename = "market_data_{$year}_" . str_pad($month, 2, '0', STR_PAD_LEFT) . ".json";

            return response()->json($payload, 200, [
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]);
        } catch (Exception $e) {
            Log::error('Error en exportación de datos de mercado', [
                'error' => $e->getMessage(),
                'line'  => $e->getLine(),
            ]);
            return response()->json(['message' => 'Error al exportar los datos.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /import-market-data
     * Importa datos de mercado desde un archivo JSON exportado previamente.
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

            // market_general_controls
            $results['market_general_controls'] = $this->importBlock(
                'market_general_controls',
                $blocks['market_general_controls'] ?? [],
                MarketGeneralControl::class,
                fn($m) => $m::where('month', $month)->where('year', $year)->exists(),
                $now
            );

            // Modelos con columna 'date'
            foreach ($this->dateModels as $key => $model) {
                $results[$key] = $this->importBlock(
                    $key,
                    $blocks[$key] ?? [],
                    $model,
                    fn($m) => $m::whereMonth('date', $month)->whereYear('date', $year)->exists(),
                    $now
                );
            }

            // Modelos con columnas 'month' y 'year'
            foreach ($this->monthYearModels as $key => $model) {
                $results[$key] = $this->importBlock(
                    $key,
                    $blocks[$key] ?? [],
                    $model,
                    fn($m) => $m::where('month', $month)->where('year', $year)->exists(),
                    $now
                );
            }

            return response()->json([
                'message' => 'Importación completada.',
                'month'   => $month,
                'year'    => $year,
                'results' => $results,
            ], 200);
        } catch (Exception $e) {
            Log::error('Error en importación de datos de mercado', [
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

        // Limpiar campos autogenerados y preparar para inserción masiva
        $toInsert = array_map(function ($record) use ($now) {
            unset($record['id']);
            $record['created_at'] = $record['created_at'] ?? $now;
            $record['updated_at'] = $now;
            // Convertir arrays/json a string para insert()
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
