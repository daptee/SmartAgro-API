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
            0 => $this->createSheetProcessor(PitIndicator::class, 1, function($row) { return $this->processPitIndicatorSheet($row); }),
            1 => $this->createSheetProcessor(LivestockInputOutputRatio::class, 1, function($row) { return $this->processLivestockInputOutputRatioSheet($row); }),
            2 => $this->createSheetProcessor(AgriculturalInputOutputRelationship::class, 1, function($row) { return $this->processAgriculturalInputOutputRelationshipSheet($row); }),
            3 => $this->createSheetProcessor(GrossMarginsTrend::class, 1, function($row) { return $this->processGrossMarginsTrendSheet($row); }),
            4 => $this->createSheetProcessor(GrossMarginsTrend2::class, 1, function($row) { return $this->processGrossMarginsTrend2Sheet($row); }),
            5 => $this->createSheetProcessor(ProductPrice::class, 1, function($row) { return $this->processProductPricesSheet($row); }),
            6 => $this->createSheetProcessor(GrossMargin::class, 1, function($row) { return $this->processGrossMarginSheet($row); }),
        ];
    }

    private function createSheetProcessor($model, $rowsToSkip, callable $rowProcessor)
    {
        return new class($model, $rowsToSkip, $rowProcessor) implements ToCollection {
            private $model;
            private $rowsToSkip;
            private $rowProcessor;

            public function __construct($model, $rowsToSkip, callable $rowProcessor)
            {
                $this->model = $model;
                $this->rowsToSkip = $rowsToSkip;
                $this->rowProcessor = $rowProcessor;
            }

            public function collection(Collection $rows)
            {
                for ($i = 0; $i < $this->rowsToSkip; $i++) {
                    $rows->shift();
                }

                if ($rows->isEmpty()) {
                    Log::error('El archivo está vacío o no contiene datos válidos.');
                    return;
                }

                foreach ($rows as $row) {
                    if ($row->filter()->isEmpty()) {
                        break;
                    }

                    $this->model::create(call_user_func($this->rowProcessor, $row));
                }
            }
        };
    }

    // Procesadores específicos para cada hoja
    private function processPitIndicatorSheet($row)
    {
        return [
            'id_plan' => $row[0],
            'date' => $row[1],
            'icon' => $row[2],
            'data' => [
                'title' => $row[3],
                'value' => $row[4],
                'text' => $row[5],
            ],
        ];
    }

    private function processLivestockInputOutputRatioSheet($row)
    {
        return [
            'id_plan' => $row[0],
            'date' => $row[1],
            'month' => $row[2],
            'region' => $row[3],
            'data' => [
                'Soja/Glifosato' => $row[4],
                'Maiz/Urea' => $row[5],
                'Trigo / Fungicida' => $row[6],
            ],
        ];
    }

    private function processAgriculturalInputOutputRelationshipSheet($row)
    {
        return [
            'id_plan' => $row[0],
            'date' => $row[1],
            'month' => $row[2],
            'region' => $row[3],
            'data' => [
                'Kg de Carne / Maiz' => $row[4],
                'Relacion Ternero / Vaca' => $row[5],
            ],
        ];
    }

    private function processGrossMarginsTrendSheet($row)
    {
        return [
            'id_plan' => $row[0],
            'date' => $row[1],
            'region' => $row[2],
            'data' => [
                'MB' => $row[3],
                '2022' => $row[4],
                '2023' => $row[5],
                '2024' => $row[6],
            ],
        ];
    }

    private function processGrossMarginsTrend2Sheet($row)
    {
        return [
            'id_plan' => $row[0],
            'date' => $row[1],
            'region' => $row[2],
            'data' => [
                'MB' => $row[3],
                'Mes Actual' => $row[4],
            ],
        ];
    }

    private function processProductPricesSheet($row)
    {
        return [
            'id_plan' => $row[0],
            'date' => $row[1],
            'data' => [
                'Marca comercial' => $row[2],
                'Activo resumido' => $row[3],
                '22/23' => $row[4],
                '23/24' => $row[5],
                '24/25' => $row[6],
            ],
        ];
    }

    private function processGrossMarginSheet($row)
    {
        return [
            'id_plan' => $row[0],
            'date' => $row[1],
            'region' => $row[2],
            'data' => [
                'Mes actual' => $row[3],
                'Maiz' => $row[4],
                'Soja' => $row[5],
                'Girasol' => $row[6],
                'Trigo' => $row[7],
            ],
        ];
    }
}