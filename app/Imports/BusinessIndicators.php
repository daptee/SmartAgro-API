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
            0 => $this->createSheetProcessor(PitIndicator::class, 1, function ($row, $headers) {
                return $this->processPitIndicatorSheet($row);
            }),
            1 => $this->createSheetProcessor(LivestockInputOutputRatio::class, 1, function ($row, $headers) {
                return $this->processLivestockInputOutputRatioSheet($row);
            }),
            2 => $this->createSheetProcessor(AgriculturalInputOutputRelationship::class, 1, function ($row, $headers) {
                return $this->processAgriculturalInputOutputRelationshipSheet($row, $headers);
            }),
            3 => $this->createSheetProcessor(GrossMarginsTrend::class, 1, function ($row, $headers) {
                return $this->processGrossMarginsTrendSheet($row);
            }),
            4 => $this->createSheetProcessor(GrossMarginsTrend2::class, 1, function ($row, $headers) {
                return $this->processGrossMarginsTrend2Sheet($row);
            }),
            5 => $this->createSheetProcessor(ProductPrice::class, 1, function ($row, $headers) {
                return $this->processProductPricesSheet($row);
            }),
            6 => $this->createSheetProcessor(GrossMargin::class, 1, function ($row, $headers) {
                return $this->processGrossMarginSheet($row);
            }),
        ];
    }

    private function createSheetProcessor($model, $rowsToSkip, callable $rowProcessor)
    {
        return new class ($model, $rowsToSkip, $rowProcessor) implements ToCollection {
            private $model;
            private $rowsToSkip;
            private $rowProcessor;
            private $headers = [];

            public function __construct($model, $rowsToSkip, callable $rowProcessor)
            {
                $this->model = $model;
                $this->rowsToSkip = $rowsToSkip;
                $this->rowProcessor = $rowProcessor;
            }

            public function collection(Collection $rows)
            {
                // Guardamos los encabezados de la primera fila
                $this->headers = $rows->shift()->toArray();

                if ($rows->isEmpty()) {
                    Log::error('El archivo está vacío o no contiene datos válidos.');
                    return;
                }

                foreach ($rows as $row) {
                    if ($row->filter()->isEmpty()) {
                        break;
                    }

                    $this->model::create(call_user_func($this->rowProcessor, $row, $this->headers));
                }
            }
        };
    }

    // Procesador para la hoja de indicadores PIT
    private function processPitIndicatorSheet($row)
    {
        return [
            'id_plan' => $row[0],
            'date' => $row[1],
            'icon' => $row[2],
            'data' => [
                'title' => $row[3],
                'value' => $row[4],
                'unit' => $row[5],
                'text' => $row[6],
            ],
        ];
    }

    // Procesador para la relación insumo-producto agrícola (con encabezados dinámicos)
    private function processAgriculturalInputOutputRelationshipSheet($row, $headers)
    {
        $data = [];

        // Empezamos desde la posición 4 porque los primeros 4 elementos son fijos (id_plan, date, etc.)
        for ($i = 4; $i < count($row); $i++) {
            if (isset($headers[$i])) {
                $data[$headers[$i]] = $row[$i] ?? null;
            }
        }

        return [
            'id_plan' => $row[0] ?? null,
            'date' => $row[1] ?? null,
            'month' => $row[2] ?? null,
            'region' => $row[3] ?? null,
            'data' => $data,
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
                'Maiz / Novillo (Kg/Kg)' => $row[4],
                'Relacion Novillo / Ternero (Kg/Kg)' => $row[5],
            ],
        ];
    }

    private function processGrossMarginsTrendSheet($row)
    {
        return [
            'id_plan' => $row[0],
            'date' => $row[1],
            'region' => $row[2],
            'month' => $row[3],
            'data' => [
                'Maiz' => $row[4],
                'Soja' => $row[5],
                'Girasol' => $row[6],
                'Trigo' => $row[7],
            ],
        ];
    }

    private function processGrossMarginsTrend2Sheet($row)
    {
        return [
            'id_plan' => $row[0],
            'date' => $row[1],
            'region' => $row[2],
            'month' => $row[3],
            'data' => [
                'Maiz' => $row[4],
                'Soja' => $row[5],
                'Girasol' => $row[6],
                'Trigo' => $row[7],
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
