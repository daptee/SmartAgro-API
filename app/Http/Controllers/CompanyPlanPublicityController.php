<?php

namespace App\Http\Controllers;

use App\Models\CompanyPlanPublicity;
use App\Models\CompanyPlanPublicitySetting;
use App\Models\Audith;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class CompanyPlanPublicityController extends Controller
{
    public function index(Request $request, $id)
    {
        $message = "Error al obtener las publicidades del plan empresa";
        $action = "Listado de publicidades del plan empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $data = CompanyPlanPublicitySetting::with('plan', 'publicity.advertisingSpace')
                ->where('id_company_plan', $id)
                ->get();

            Audith::new($id_user, $action, $request->all(), 200, compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact('data'));
    }

    public function store(Request $request)
    {
        $message = "Error al guardar la publicidad personalizada";
        $action = "Creación de publicidad del plan empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $validated = $request->validate([
                'id_company_plan' => 'required|exists:companies,id',
                'id_advertising_space' => 'required|exists:advertising_spaces,id',
                'file' => 'nullable|file|mimes:gif|max:10240',
                'is_active' => 'boolean',
            ]);

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

            $data = CompanyPlanPublicity::create([
                'id_company_plan' => $validated['id_company_plan'],
                'id_advertising_space' => $validated['id_advertising_space'],
                'gif_path' => $filePath,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            $data->load('advertisingSpace');

            Audith::new($id_user, $action, $request->all(), 201, compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact('data'), 201);
    }

    public function update(Request $request, $id)
    {
        $message = "Error al actualizar la publicidad personalizada";
        $action = "Actualización de publicidad del plan empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $validated = $request->validate([
                'id_company_plan' => 'required|exists:companies,id',
                'id_advertising_space' => 'required|exists:advertising_spaces,id',
                'file' => 'nullable|file|mimes:gif|max:10240',
                'is_active' => 'boolean',
            ]);

            $publicity = CompanyPlanPublicity::findOrFail($id);

            // Verifica que la empresa coincida
            if ($publicity->id_company_plan != $validated['id_company_plan']) {
                return response(["message" => "No tienes permisos para editar esta publicidad"], 403);
            }

            $imagePath = public_path('storage/publicities/gifs/');
            if (!file_exists($imagePath)) {
                mkdir($imagePath, 0777, true);
            }

            if ($request->hasFile('file')) {
                // Elimina el archivo anterior si existe
                if ($publicity->gif_path && file_exists(public_path($publicity->gif_path))) {
                    unlink(public_path($publicity->gif_path));
                }

                $file = $request->file('file');
                $fileName = time() . '_file_' . $file->getClientOriginalName();
                $file->move($imagePath, $fileName);
                $publicity->gif_path = '/storage/publicities/gifs/' . $fileName;
            }

            $publicity->id_advertising_space = $validated['id_advertising_space'];
            $publicity->is_active = $validated['is_active'] ?? $publicity->is_active;
            $publicity->save();

            $data = $publicity->fresh('advertisingSpace');

            Audith::new($id_user, $action, $request->all(), 200, compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact('data'));
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
