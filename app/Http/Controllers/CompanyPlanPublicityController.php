<?php

namespace App\Http\Controllers;

use App\Models\CompanyPlanPublicity;
use App\Models\CompanyPlanPublicitySetting;
use App\Models\Audith;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;
use Log;

class CompanyPlanPublicityController extends Controller
{
    public function index(Request $request, $id)
    {
        $message = "Error al obtener las publicidades del plan empresa";
        $action = "Listado de publicidades del plan empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $data = CompanyPlanPublicitySetting::with('plan')
                ->where('id_company_plan', $id)
                ->first();

            if (!$data) {
                Audith::new($id_user, $action, $request->all(), 404, "No se encontró la configuración de publicidad para el plan de empresa.");
                return response(["message" => $message, "error" => "No se encontró la configuración de publicidad para el plan de empresa."], 404);
            };

            $data->publicity = CompanyPlanPublicity::with('advertisingSpace')
                ->where('id_company_plan', $id)
                ->get();    

            Log::info('Data retrieved successfully', ['data' => $data]);

            Audith::new($id_user, $action, $request->all(), 200, compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact('data'));
    }

    public function upsertAll(Request $request)
    {
        $message = "Error al procesar las publicidades del plan empresa";
        $action = "Creación/actualización masiva de publicidades del plan empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $validated = $request->validate([
                'id_company_plan' => 'required|exists:companies,id',
                'publicities' => 'required|array',
                'publicities.*.id' => 'nullable|exists:company_plan_publicities,id',
                'publicities.*.id_advertising_space' => 'required|exists:advertising_spaces,id',
                'publicities.*.is_active' => 'boolean',
                'publicities.*.file' => 'nullable', // Cambiado para permitir string "null"
            ]);

            $results = [];
            $imagePath = public_path('storage/publicities/gifs/');

            if (!file_exists($imagePath)) {
                mkdir($imagePath, 0777, true);
            }

            $setting = CompanyPlanPublicitySetting::where('id_company_plan', $validated['id_company_plan'])->first();

            if ($setting && $setting->show_any_ads == 0) {
                Audith::new($id_user, $action, $request->all(), 403, "Las publicidades están desactivadas para este plan de empresa.");
                return response(["message" => $message, "error" => "Las publicidades están desactivadas para este plan de empresa."], 403);
            }

            foreach ($request->publicities as $item) {
                $filePath = null;

                // Manejo del archivo
                if (isset($item['file']) && $item['file'] instanceof \Illuminate\Http\UploadedFile) {
                    $file = $item['file'];
                    $fileName = time() . '_file_' . $file->getClientOriginalName();
                    $file->move($imagePath, $fileName);
                    $filePath = '/storage/publicities/gifs/' . $fileName;
                } elseif (isset($item['file']) && $item['file'] === "null") {
                    $filePath = true; // Mantener el archivo existente
                }

                // Actualización
                if (isset($item['id'])) {
                    $publicity = CompanyPlanPublicity::findOrFail($item['id']);

                    if ($publicity->id_company_plan != $validated['id_company_plan']) {
                        continue;
                    }

                    if ($filePath) {
                        // Eliminar archivo anterior si se sube uno nuevo
                        if ($publicity->gif_path && file_exists(public_path($publicity->gif_path))) {
                            unlink(public_path($publicity->gif_path));
                        }

                        $publicity->update([
                            'id_advertising_space' => $item['id_advertising_space'],
                            'is_active' => $item['is_active'] ?? $publicity->is_active,
                            'gif_path' => $filePath !== true ? $filePath : $publicity->gif_path,
                        ]);

                        $results[] = $publicity->fresh('advertisingSpace');
                    } elseif ($filePath === null) {
                        // Eliminar archivo anterior si se sube uno nuevo
                        if ($publicity->gif_path && file_exists(public_path($publicity->gif_path))) {
                            unlink(public_path($publicity->gif_path));
                        }
                        $publicity->update([
                            'id_advertising_space' => $item['id_advertising_space'],
                            'is_active' => $item['is_active'] ?? $publicity->is_active,
                            'gif_path' => null,
                        ]);
                        $results[] = $publicity->fresh('advertisingSpace');
                    }
                } else {
                    // Creación
                    $publicity = CompanyPlanPublicity::create([
                        'id_company_plan' => $validated['id_company_plan'],
                        'id_advertising_space' => $item['id_advertising_space'],
                        'gif_path' => $filePath,
                        'is_active' => $item['is_active'] ?? true,
                    ]);

                    $results[] = $publicity->load('advertisingSpace');
                }
            }

            $data = $results;
            Audith::new($id_user, $action, $request->all(), 200, compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact('data'), 200);
    }

    public function toggleGlobalAds(Request $request, $id)
    {
        $message = "Error al actualizar la configuración general de publicidad";
        $action = "Actualización de configuración general de publicidad del plan empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $validated = $request->validate([
                'show_any_ads' => 'required|boolean',
            ]);

            $data = CompanyPlanPublicitySetting::updateOrCreate(
                ['id_company_plan' => $id],
                ['show_any_ads' => $validated['show_any_ads']]
            );

            Audith::new($id_user, $action, $request->all(), 200, compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact('data'));
    }
}
