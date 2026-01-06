<?php

namespace App\Imports;

use App\Models\MainCropsBuyingSellingTrafficLight;
use App\Models\PitIndicator;
use App\Models\LivestockInputOutputRatio;
use App\Models\AgriculturalInputOutputRelationship;
use App\Models\GrossMarginsTrend;
use App\Models\HarvestPrices;
use App\Models\ProductPrice;
use App\Models\GrossMargin;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class BusinessIndicators implements WithMultipleSheets
{
    /**
     * Convierte un valor de fecha de Excel a formato de fecha válido
     *
     * @param mixed $value
     * @return string|null
     */
    public function convertExcelDate($value)
    {
        if (empty($value)) {
            return null;
        }

        // Si es un número (serial de Excel), convertirlo a fecha
        if (is_numeric($value)) {
            try {
                $date = Date::excelToDateTimeObject($value);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                Log::error("Error al convertir fecha de Excel: {$value}", ['error' => $e->getMessage()]);
                return null;
            }
        }

        // Si ya es una fecha en formato string, intentar parsearla
        try {
            $date = new \DateTime($value);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            Log::error("Error al parsear fecha: {$value}", ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function sheets(): array
    {
        return [
            0 => $this->createSheetProcessor(PitIndicator::class, ['id_plan', 'date', 'icon']),
            1 => $this->createSheetProcessor(LivestockInputOutputRatio::class, ['id_plan', 'date', 'month', 'region'], true),
            2 => $this->createSheetProcessor(AgriculturalInputOutputRelationship::class, ['id_plan', 'date', 'month', 'region'], true),
            3 => $this->createSheetProcessor(GrossMarginsTrend::class, ['id_plan', 'date', 'region', 'month']),
            4 => $this->createSheetProcessor(HarvestPrices::class, ['id_plan', 'date', 'region', 'month']),
            5 => $this->createSheetProcessor(ProductPrice::class, ['id_plan', 'date', 'segment_id']),
            6 => $this->createSheetProcessor(GrossMargin::class, ['id_plan', 'date', 'region']),
            7 => $this->createSheetProcessor(MainCropsBuyingSellingTrafficLight::class, ['id_plan', 'date', 'input', 'variable']),
        ];
    }

    private function createSheetProcessor($model, $fixedFields, $mergePercentage = false)
    {
        return new class ($model, $fixedFields, $this, $mergePercentage) implements ToCollection {
            private $model;
            private $fixedFields;
            private $importer;
            private $headers = [];
            private $mergePercentage;

            public function __construct($model, $fixedFields, $importer, $mergePercentage)
            {
                $this->model = $model;
                $this->fixedFields = $fixedFields;
                $this->importer = $importer;
                $this->mergePercentage = $mergePercentage;
            }

            public function collection(Collection $rows)
            {
                if ($rows->isEmpty()) {
                    Log::error('El archivo está vacío o no contiene datos válidos.');
                    return;
                }

                $this->headers = $rows->shift()->toArray();

                foreach ($rows as $row) {
                    if ($row->filter()->isEmpty()) {
                        break;
                    }

                    $parsed = $this->importer->processDynamicSheet(
                        $row,
                        $this->headers,
                        $this->fixedFields,
                        $this->mergePercentage
                    );

                    $this->model::create($parsed);
                }
            }
        };
    }

    public function processDynamicSheet($row, $headers, $fixedFields, $mergePercentage = false)
    {
        $rowArray = $row->toArray();
        $result = [];
        $data = [];

        $valueMap = [];
        $percentageMap = [];

        foreach ($headers as $index => $header) {
            $value = $rowArray[$index] ?? null;
            $normalizedHeader = trim($header);

            if ($normalizedHeader === '')
                continue;

            $mappedHeader = match ($normalizedHeader) {
                'Plan' => 'id_plan',
                'Fecha' => 'date',
                'Icono' => 'icon',
                'Mes' => 'month',
                'Region' => 'region',
                'Segmento' => 'segment_id',
                'Insumo' => 'input',
                'Variable' => 'variable',
                default => $normalizedHeader,
            };

            if (in_array($mappedHeader, $fixedFields)) {
                // Si es el campo 'date', convertir desde el formato Excel
                if ($mappedHeader === 'date') {
                    $result[$mappedHeader] = $this->importer->convertExcelDate($value);
                } else {
                    $result[$mappedHeader] = $value;
                }
            } elseif ($mergePercentage && str_ends_with($normalizedHeader, '%')) {
                // Caso: "producción %54 ganadera %" → base: "producción %54 ganadera"
                $baseKey = rtrim($normalizedHeader, ' %');
                $percentageMap[$baseKey] = is_numeric($value) ? (float) $value : null;
            } else {
                $valueMap[$normalizedHeader] = $value ?? null;
            }
        }

        // Combinar valores con sus porcentajes
        foreach ($valueMap as $key => $val) {
            if ($mergePercentage && isset($percentageMap[$key])) {
                $data[$key] = [
                    'value' => $val,
                    'percentage' => $percentageMap[$key]
                ];
            } else {
                $data[$key] = $val;
            }
        }

        $result['data'] = $data;
        return $result;
    }
}