<?php

namespace App\Imports;

use App\Models\Insight;
use App\Models\MagLeaseIndex;
use App\Models\MagSteerIndex;
use App\Models\MainGrainPrice;
use App\Models\MajorCrop;
use App\Models\Plan;
use App\Models\News;
use App\Models\PriceMainActiveIngredientsProducer;
use App\Models\ProducerSegmentPrice;
use App\Models\RainfallRecordProvince;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ExcelImport implements WithMultipleSheets
{
    /**
     * Convierte un valor de fecha de Excel a formato de fecha válido
     *
     * @param mixed $value
     * @return string|null
     */
    private function convertExcelDate($value)
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
            0 => $this->createSheetProcessor(News::class, 1, function($row) { return $this->processNewsSheet($row); }),
            1 => $this->createSheetProcessor(MajorCrop::class, 2, function($row) { return $this->processMajorCropSheet($row); }),
            2 => $this->createSheetProcessor(MagLeaseIndex::class, 1, function($row) { return $this->processMagLeaseIndexSheet($row); }),
            3 => $this->createSheetProcessor(MagSteerIndex::class, 1, function($row) { return $this->processMagSteerIndexSheet($row); }),
            4 => $this->createSheetProcessor(Insight::class, 1, function($row) { return $this->processInsightSheet($row); }),
            5 => $this->createSheetProcessor(PriceMainActiveIngredientsProducer::class, 1, function($row) { return $this->processPriceMainActiveIngredientsProducerSheet($row); }),
            6 => $this->createSheetProcessor(ProducerSegmentPrice::class, 1, function($row) { return $this->processProducerSegmentPriceSheet($row); }),
            7 => $this->createSheetProcessor(RainfallRecordProvince::class, 1, function($row) { return $this->processRainfallRecordProvinceSheet($row); }),
            8 => $this->createSheetProcessor(MainGrainPrice::class, 1, function($row) { return $this->processMainGrainPriceSheet($row); }),
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
                // Eliminar las primeras filas según la cantidad especificada
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

                    // Procesar la fila y crear el registro en la base de datos
                    $this->model::create(call_user_func($this->rowProcessor, $row));
                }
            }
        };
    }

    // Funciones específicas para cada hoja
    private function processNewsSheet($row)
    {
        return [
            'img' => $row[2],
            'title' => $row[3],
            'new' => $row[4],
            'date' => $this->convertExcelDate($row[1]),
            'id_plan' => $row[0],
        ];
    }

    private function processMajorCropSheet($row)
    {
        $jsonData = [
            'avance cosecha' => ['% Cosechado 24/25' => $row[4]],
            'area sembrada (ha)' => [
                '%  Sembrado 25/26' => $row[3],
                '24/25' => $row[5],
                '25/26' => $row[6],
                'var (%)' => $row[7]
            ],
            'rinde (qq/ha)' => [
                '24/25' => $row[8],
                '25/26' => $row[9],
                'var (%)' => $row[10]
            ],
            'produccion (Ton)' => [
                '24/25' => $row[11],
                '25/26 (proyección)' => $row[12],
                'var (%)' => $row[13]
            ]
        ];
        return [
            'id_plan' => $row[0],
            'date' => $this->convertExcelDate($row[1]),
            'icon' => $row[2],
            'data' => $jsonData,
        ];
    }

    private function processMagLeaseIndexSheet($row)
    {
        $jsonData = [
            'I.A.MAG ($)' => $row[2],
            'min' => $row[3],
            'max' => $row[4],
            'prom' => $row[5],
            'Var (%)*' => $row[6]
        ];
        return [
            'id_plan' => $row[0],
            'date' => $this->convertExcelDate($row[1]),
            'data' => $jsonData,
        ];
    }

    private function processMagSteerIndexSheet($row)
    {
        $jsonData = [
            'INMAG' => $row[2],
            'min' => $row[3],
            'max' => $row[4],
            'prom' => $row[5],
            'Var (%)*' => $row[6]
        ];
        return [
            'id_plan' => $row[0],
            'date' => $this->convertExcelDate($row[1]),
            'data' => $jsonData,
        ];
    }

    private function processInsightSheet($row)
    {

        return [
            'id_plan' => $row[0],
            'date' => $this->convertExcelDate($row[1]),
            'icon' => $row[2],
            'title' => $row[3],
            'description' => $row[4],
        ];
    }

    private function processPriceMainActiveIngredientsProducerSheet($row)
    {
        $jsonData = [
            'activo' => $row[2],
            'nomenclatura resumida' => $row[3],
            '24/25' => $row[5],
            '25/26' => $row[6],
        ];

        return [
            'id_plan' => $row[0],
            'date' => $this->convertExcelDate($row[1]),
            'data' => $jsonData,
            'segment_id' => $row[4],
        ];
    }

    private function processProducerSegmentPriceSheet($row)
    {
        $jsonData = [
            'USD/Kg o Lt' => $row[2],
            '24/25' => $row[3],
            '25/26' => $row[4],
        ];

        return [
            'id_plan' => $row[0],
            'date' => $this->convertExcelDate($row[1]),
            'data' => $jsonData,
        ];
    }

    private function processRainfallRecordProvinceSheet($row)
    {
        $jsonData = [
            'REGISTRO DE LLUVIAS X PROVINCIA' => $row[2],
            'PROM 24/25' => $row[3],
            'ACUM 24/25' => $row[4],
            'PROM 25/26' => $row[5],
            'ACUM 25/26' => $row[6],
            'Var. Acum 24 Vs 23' => $row[7],
        ];

        return [
            'id_plan' => $row[0],
            'date' => $this->convertExcelDate($row[1]),
            'data' => $jsonData,
            'status_id' => 1,
        ];
    }

    private function processMainGrainPriceSheet($row)
    {
        $jsonData = [
            'Icono' => $row[2],
            'Min' => $row[3],
            'Max' => $row[4],
            'Prom' => $row[5],
        ];
        return [
            'id_plan' => $row[0],
            'date' => $this->convertExcelDate($row[1]),
            'data' => $jsonData,
        ];
    }
}