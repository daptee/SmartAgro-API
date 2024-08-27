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
use Carbon\Carbon;

class NewsImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            // Procesar primera hoja
                // 0 => new class implements ToCollection {
                //     public function collection(Collection $rows)
                //     {
                //         // Ignorar la primera fila que contiene los encabezados
                //         $rows->shift(); // Elimina la primera fila

                //         // Verificar si hay filas para procesar
                //         if ($rows->isEmpty()) {
                //             Log::error('El archivo está vacío o no contiene datos válidos.');
                //             return; // Salir del método si no hay datos
                //         }

                //         foreach ($rows as $index => $row) {
                //             // Verificar si la fila está vacía
                //             if ($row->filter()->isEmpty()) {
                //                 // Log::warning('Fila vacía detectada en la importación', ['index' => $index, 'row' => $row]);
                                
                //                 // Detener el proceso si se detecta una fila vacía
                //                 // dd('Fila vacía detectada en la importación. Proceso detenido.', ['index' => $index, 'row' => $row]); // O usa return; para solo detener la importación
                //                 break; // Salir del bucle foreach
                //             }

                //             // // Convertir la fecha al formato deseado
                //             // $formattedDate = Carbon::createFromFormat('d/m/Y', $row[1])->format('Y-m-d');

                //             // Buscar el ID del plan usando el nombre del plan en la primera columna (índice 0)
                //             $plan = Plan::where('plan', $row[0])->first();

                //             // Crear el registro en la base de datos
                //             News::create([
                //                 'img' => $row[2], // Link Imagen (índice 2)
                //                 'title' => $row[3], // Titulo (índice 3)
                //                 'new' => isset($row[4]) ? json_encode(['description' => $row[4]]) : null, // Descripción / Noticia (índice 4)
                //                 'date' => $row[1], // Fecha convertida (índice 1)
                //                 'id_plan' => $plan ? $plan->id : null, // Asignar el ID del plan encontrado
                //             ]);
                //         }
                //     }
                // },
            // END: Procesar primera hoja

            // Procesar segunda hoja
                // 1 => new class implements ToCollection {
                //     public function collection(Collection $rows)
                //     {
                //         // Eliminar las dos primeras filas (encabezados y segunda fila)
                //         $rows->shift(); // Elimina la primera fila
                //         $rows->shift(); // Elimina la segunda fila

                //         if ($rows->isEmpty()) {
                //             Log::error('El archivo está vacío o no contiene datos válidos.');
                //             return;
                //         }

                //         foreach ($rows as $index => $row) {
                //             if ($row->filter()->isEmpty()) {
                //                 // Log::warning('Fila vacía detectada en la importación de la segunda hoja', ['index' => $index, 'row' => $row]);
                //                 break;
                //             }

                //             // Crear el JSON con el formato requerido
                //             $jsonData = [
                //                 'avance cosecha' => [
                //                     '% Cosechado 23/24' => $row[4]
                //                 ],
                //                 'area sembrada (ha)' => [
                //                     '%  Sembrado 24/25' => $row[5],
                //                     '23/24' => $row[6],
                //                     '24/25' => $row[7],
                //                     'var (%)' => $row[8]
                //                 ],
                //                 'rinde (qq/ha)' => [
                //                     '23/24' => $row[9],
                //                     '24/25' => $row[10],
                //                     'var (%)' => $row[11]
                //                 ],
                //                 'produccion (Ton)' => [
                //                     '23/24' => $row[12],
                //                     '24/25 (proyección)' => $row[13],
                //                     'var (%)' => $row[14]
                //                 ]
                //             ];

                //             // $formattedDate = Carbon::createFromFormat('d/m/Y', $row[1])->format('Y-m-d');
                //             $plan = Plan::where('plan', $row[0])->first();

                //             MajorCrop::create([
                //                 'id_plan' => $plan ? $plan->id : null,
                //                 'date' => $row[1],
                //                 'icon' => $row[3],
                //                 'data' => $jsonData,
                //             ]);
                //         }
                //     }
                // }
            // END: Procesar segunda hoja

            // Procesar tercera hoja
                // 2 => new class implements ToCollection {
                //     public function collection(Collection $rows)
                //     {
                //         // Eliminar la primera fila que contiene los encabezados
                //         $rows->shift(); // Elimina la primera fila

                //         if ($rows->isEmpty()) {
                //             Log::error('El archivo está vacío o no contiene datos válidos.');
                //             return;
                //         }

                //         foreach ($rows as $index => $row) {
                //             if ($row->filter()->isEmpty()) {
                //                 break; // Salir del bucle foreach si la fila está vacía
                //             }

                //             // Crear el JSON con el formato requerido
                //             $jsonData = [
                //                 'I.A.MAG ($)' => $row[2],
                //                 'min' => $row[3],
                //                 'max' => $row[4],
                //                 'prom' => $row[5],
                //                 'Var (%)*' => $row[6]
                //             ];

                //             $plan = Plan::where('plan', $row[0])->first();

                //             MagLeaseIndex::create([
                //                 'id_plan' => $plan ? $plan->id : null,
                //                 'date' => $row[1],
                //                 'data' => $jsonData, // Guardar el JSON en el campo 'data'
                //             ]);
                //         }
                //     }
                // }
            // END: Procesar tercera hoja

            // Procesar cuarta hoja
                // 3 => new class implements ToCollection {
                //     public function collection(Collection $rows)
                //     {
                //         // Eliminar la primera fila que contiene los encabezados
                //         $rows->shift(); // Elimina la primera fila

                //         if ($rows->isEmpty()) {
                //             Log::error('El archivo está vacío o no contiene datos válidos.');
                //             return;
                //         }

                //         foreach ($rows as $index => $row) {
                //             if ($row->filter()->isEmpty()) {
                //                 break; // Salir del bucle foreach si la fila está vacía
                //             }

                //             // Crear el JSON con el formato requerido
                //             $jsonData = [
                //                 'INMAG' => $row[2],
                //                 'min' => $row[3],
                //                 'max' => $row[4],
                //                 'prom' => $row[5],
                //                 'Var (%)*' => $row[6]
                //             ];

                //             $plan = Plan::where('plan', $row[0])->first();

                //             MagSteerIndex::create([
                //                 'id_plan' => $plan ? $plan->id : null,
                //                 'date' => $row[1],
                //                 'data' => $jsonData, // Guardar el JSON en el campo 'data'
                //             ]);
                //         }
                //     }
                // }
            // END: Procesar cuarta hoja

            // Procesar quinta hoja
                // 4 => new class implements ToCollection {
                //     public function collection(Collection $rows)
                //     {
                //         // Ignorar la primera fila que contiene los encabezados
                //         $rows->shift(); // Elimina la primera fila

                //         // Verificar si hay filas para procesar
                //         if ($rows->isEmpty()) {
                //             Log::error('El archivo está vacío o no contiene datos válidos.');
                //             return; // Salir del método si no hay datos
                //         }

                //         foreach ($rows as $index => $row) {

                //             if ($row->filter()->isEmpty()) {
                //                 break;
                //             }

                //             $plan = Plan::where('plan', $row[0])->first();

                //             Insight::create([
                //                 'id_plan' => $plan ? $plan->id : null,
                //                 'date' => $row[1],
                //                 'icon' => $row[2],
                //                 'title' => $row[3],
                //                 'description' => $row[4],
                //             ]);
                //         }
                //     }
                // },
            // END: Procesar quinta hoja

            // Procesar sexta hoja
                // 5 => new class implements ToCollection {
                //     public function collection(Collection $rows)
                //     {
                //         $rows->shift();

                //         if ($rows->isEmpty()) {
                //             Log::error('El archivo está vacío o no contiene datos válidos.');
                //             return; // Salir del método si no hay datos
                //         }

                //         foreach ($rows as $index => $row) {

                //             if ($row->filter()->isEmpty()) {
                //                 break;
                //             }

                //             $jsonData = [
                //                 'activo' => $row[2],
                //                 '23/24' => $row[3],
                //                 '24/25' => $row[4],
                //             ];

                //             $plan = Plan::where('plan', $row[0])->first();

                //             PriceMainActiveIngredientsProducer::create([
                //                 'id_plan' => $plan ? $plan->id : null,
                //                 'date' => $row[1],
                //                 'data' => $jsonData,
                //             ]);
                //         }
                //     }
                // },
            // END: Procesar sexta hoja

            // Procesar septima hoja
                // 6 => new class implements ToCollection {
                //     public function collection(Collection $rows)
                //     {
                //         $rows->shift();

                //         if ($rows->isEmpty()) {
                //             Log::error('El archivo está vacío o no contiene datos válidos.');
                //             return; // Salir del método si no hay datos
                //         }

                //         foreach ($rows as $index => $row) {

                //             if ($row->filter()->isEmpty()) {
                //                 break;
                //             }

                //             $jsonData = [
                //                 'USD/Kg o Lt' => $row[2],
                //                 '23/24' => $row[3],
                //                 '24/25' => $row[4],
                //             ];

                //             $plan = Plan::where('plan', $row[0])->first();

                //             ProducerSegmentPrice::create([
                //                 'id_plan' => $plan ? $plan->id : null,
                //                 'date' => $row[1],
                //                 'data' => $jsonData,
                //             ]);
                //         }
                //     }
                // },
            // END: Procesar septima hoja

            // Procesar octava hoja
                // 7 => new class implements ToCollection {
                //     public function collection(Collection $rows)
                //     {
                //         $rows->shift();

                //         if ($rows->isEmpty()) {
                //             Log::error('El archivo está vacío o no contiene datos válidos.');
                //             return; // Salir del método si no hay datos
                //         }

                //         foreach ($rows as $index => $row) {

                //             if ($row->filter()->isEmpty()) {
                //                 break;
                //             }

                //             $jsonData = [
                //                 'REGISTRO DE LLUVIAS X PROVINCIA' => $row[2],
                //                 'PROM Julio 23' => $row[3],
                //                 'ACUM Julio 23' => $row[4],
                //                 'PROM Julio 24' => $row[5],
                //                 'ACUM Julio 24' => $row[6],
                //                 'Var. Acum 24 Vs 23' => $row[7],
                //             ];

                //             $plan = Plan::where('plan', $row[0])->first();

                //             RainfallRecordProvince::create([
                //                 'id_plan' => $plan ? $plan->id : null,
                //                 'date' => $row[1],
                //                 'data' => $jsonData,
                //             ]);
                //         }
                //     }
                // },
            // END: Procesar octava hoja

            // Procesar novena hoja
                8 => new class implements ToCollection {
                    public function collection(Collection $rows)
                    {
                        $rows->shift();

                        if ($rows->isEmpty()) {
                            Log::error('El archivo está vacío o no contiene datos válidos.');
                            return; // Salir del método si no hay datos
                        }

                        foreach ($rows as $index => $row) {

                            if ($row->filter()->isEmpty()) {
                                break;
                            }

                            $jsonData = [
                                'Icono' => $row[3],
                                'Min' => $row[4],
                                'Max' => $row[5],
                                'Prom' => $row[6],
                            ];

                            $plan = Plan::where('plan', $row[0])->first();

                            MainGrainPrice::create([
                                'id_plan' => $plan ? $plan->id : null,
                                'date' => $row[1],
                                'data' => $jsonData,
                            ]);
                        }
                    }
                },
            // END: Procesar octava hoja
        ];
    }
}