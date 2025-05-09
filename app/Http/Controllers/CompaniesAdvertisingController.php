<?php

namespace App\Http\Controllers;

use App\Models\Audith;
use App\Models\CompanyAdvertising;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Exception;

class CompaniesAdvertisingController extends Controller
{
    public function index(Request $request)
    {
        $message = "Error al obtener las publicidades contratadas";
        $action = "Listado de publicidades contratadas";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $perPage = $request->query('per_page', 10);
            $page = $request->query('page', 1);

            $query = CompanyAdvertising::with(['advertising_space', 'company.category', 'company.status', 'company.locality', 'company.plan', 'status']);

            if ($request->filled('advertising_space')) {
                $query->where('id_advertising_space', $request->advertising_space);
            }

            if ($request->filled('company')) {
                $query->where('id_company', $request->company);
            }

            if ($request->filled('status')) {
                $query->where('id_advertising_status', $request->status);
            }

            if ($request->filled('date_start') && $request->filled('date_end')) {
                $query->where(function ($q) use ($request) {
                    $q->whereBetween('date_start', [$request->date_start, $request->date_end])
                        ->orWhereBetween('date_end', [$request->date_start, $request->date_end])
                        ->orWhere(function ($q2) use ($request) {
                            $q2->where('date_start', '<', $request->date_start)
                                ->where('date_end', '>', $request->date_end);
                        });
                });
            }

            $results = $query->paginate($perPage, ['*'], 'page', $page);

            $data = [
                'result' => $results->items(),
                'meta_data' => [
                    'page' => $results->currentPage(),
                    'per_page' => $results->perPage(),
                    'total' => $results->total(),
                    'last_page' => $results->lastPage(),
                ]
            ];

            Audith::new($id_user, $action, $request->all(), 200, compact('data'));
            return response(compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $message = "Error al crear la publicidad contratada";
        $action = "Creación de publicidad contratada";
        $id_user = Auth::user()->id ?? null;

        try {
            $validated = $request->validate([
                'id_advertising_space' => 'required|integer|exists:advertising_spaces,id',
                'id_company' => 'required|integer|exists:companies,id',
                'date_start' => 'required|date',
                'date_end' => 'required|date|after_or_equal:date_start',
                'price' => 'required|numeric',
                'link' => 'required|url',
                'id_advertising_status' => 'required|integer|exists:advertising_status,id',
                'file' => 'nullable|file|mimes:gif|max:10240', // max 10MB
                'additional_data' => 'nullable|string', // Se espera string JSON
            ]);

            if (isset($validated['additional_data'])) {
                $jsonDecoded = json_decode($validated['additional_data'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $validated['additional_data'] = $jsonDecoded;
                } else {
                    return response([
                        'message' => 'El campo additional_data no contiene un JSON válido.',
                        'error' => json_last_error_msg()
                    ], 422);
                }
            }

            $imagePath = public_path('storage/publicities/gifs/');

            if (!file_exists($imagePath)) {
                mkdir($imagePath, 0777, true);
            }

            $filePath = null;

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileName = time() . '_file_' . $file->getClientOriginalName();
                $file->move($imagePath, $fileName);
                $filePath = '/storage/publicities/gifs/' . $fileName;
            }

            $data = CompanyAdvertising::create([
                'id_advertising_space' => $validated['id_advertising_space'],
                'id_company' => $validated['id_company'],
                'date_start' => $validated['date_start'],
                'date_end' => $validated['date_end'],
                'price' => $validated['price'],
                'link' => $validated['link'],
                'file' => $filePath,
                'id_advertising_status' => $validated['id_advertising_status'],
                'additional_data' => $validated['additional_data'] ?? null,
            ]);

            $data->load('advertising_space', 'company', 'status');

            Audith::new($id_user, $action, $request->all(), 201, compact('data'));

            return response(compact('data'), 201);
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $message = "Error al actualizar la publicidad contratada";
        $action = "Actualización de publicidad contratada";
        $id_user = Auth::user()->id ?? null;

        try {
            $validated = $request->validate([
                'id_advertising_space' => 'required|integer|exists:advertising_spaces,id',
                'id_company' => 'required|integer|exists:companies,id',
                'date_start' => 'required|date',
                'date_end' => 'required|date|after_or_equal:date_start',
                'price' => 'required|numeric',
                'link' => 'required|url',
                'id_advertising_status' => 'required|integer|exists:advertising_status,id',
                'file' => 'nullable|file|mimes:gif|max:10240',
                'additional_data' => 'nullable|string',
            ]);

            if (isset($validated['additional_data'])) {
                $jsonDecoded = json_decode($validated['additional_data'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $validated['additional_data'] = $jsonDecoded;
                } else {
                    return response([
                        'message' => 'El campo additional_data no contiene un JSON válido.',
                        'error' => json_last_error_msg()
                    ], 422);
                }
            }

            $record = CompanyAdvertising::findOrFail($id);

            $imagePath = public_path('storage/publicities/gifs/');

            // Crear carpeta si no existe
            if (!file_exists($imagePath)) {
                mkdir($imagePath, 0777, true);
            }

            if ($request->hasFile('file')) {
                if ($record->file && file_exists(public_path($record->file))) {
                    unlink(public_path($record->file));
                }
                $file = $request->file('file');
                $fileName = time() . '_file_' . $file->getClientOriginalName();
                $file->move($imagePath, $fileName);
                $record->file = '/storage/publicities/gifs/' . $fileName;
            } elseif ($record->file === null) {
                if ($record->file && file_exists(public_path($record->file))) {
                    unlink(public_path($record->file));
                }

                $record->file = null;
            }

            $record->update([
                'id_advertising_space' => $validated['id_advertising_space'],
                'id_company' => $validated['id_company'],
                'date_start' => $validated['date_start'],
                'date_end' => $validated['date_end'],
                'price' => $validated['price'],
                'link' => $validated['link'],
                'id_advertising_status' => $validated['id_advertising_status'],
                'additional_data' => $validated['additional_data'] ?? null,
            ]);

            $data = $record;

            $data->load('advertising_space', 'company', 'status');

            Audith::new($id_user, $action, $request->all(), 200, compact('data'));

            return response(compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }
    }
}
