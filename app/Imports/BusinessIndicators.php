<?php

namespace App\Imports;

use App\Models\PitIndicator;
use App\Models\LivestockInputOutputRatio;
use App\Models\AgriculturalInputOutputRelationship;
use App\Models\GrossMarginsTrend;
use App\Models\GrossMarginsTrend2;
use App\Models\ProductPrice;
use App\Models\GrossMargin;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BusinessIndicators implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            0 => $this->createSheetProcessor(PitIndicator::class, ['id_plan', 'date', 'icon']),
            1 => $this->createSheetProcessor(LivestockInputOutputRatio::class, ['id_plan', 'date', 'month', 'region']),
            2 => $this->createSheetProcessor(AgriculturalInputOutputRelationship::class, ['id_plan', 'date', 'month', 'region']),
            3 => $this->createSheetProcessor(GrossMarginsTrend::class, ['id_plan', 'date', 'region', 'month']),
            4 => $this->createSheetProcessor(GrossMarginsTrend2::class, ['id_plan', 'date', 'region', 'month']),
            5 => $this->createSheetProcessor(ProductPrice::class, ['id_plan', 'date']),
            6 => $this->createSheetProcessor(GrossMargin::class, ['id_plan', 'date', 'region']),
        ];
    }

    private function createSheetProcessor($model, $fixedFields)
    {
        return new class ($model, $fixedFields, $this) implements ToCollection {
            private $model;
            private $fixedFields;
            private $importer;
            private $headers = [];

            public function __construct($model, $fixedFields, $importer)
            {
                $this->model = $model;
                $this->fixedFields = $fixedFields;
                $this->importer = $importer;
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

                    $this->model::create($this->importer->processDynamicSheet(
                        $row,
                        $this->headers,
                        $this->fixedFields
                    ));
                }
            }
        };
    }

    public function processDynamicSheet($row, $headers, $fixedFields)
    {
        $rowArray = $row->toArray();
        $result = [];
        $data = [];

        foreach ($headers as $index => $header) {
            $value = $rowArray[$index] ?? null;
            $normalizedHeader = trim($header); 

            if ($normalizedHeader === '') {
                continue; 
            }

            
            $mappedHeader = match ($normalizedHeader) {
                'Plan' => 'id_plan',
                'Fecha' => 'date',
                'Icono' => 'icon',
                'Mes' => 'month',
                'Region' => 'region',
                default => $normalizedHeader,
            };

            if (in_array($mappedHeader, $fixedFields)) {
                $result[$mappedHeader] = $value;
            } else {
                $data[$normalizedHeader] = $value;
            }
        }

        $result['data'] = $data;
        return $result;
    }

}
