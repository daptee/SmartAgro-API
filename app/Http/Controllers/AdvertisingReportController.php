<?php

namespace App\Http\Controllers;

use App\Models\AdvertisingReport;
use App\Models\Audith;
use App\Models\CompanyAdvertising;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class AdvertisingReportController extends Controller
{
    public function index(Request $request)
    {
        $message = "Error al obtener los reportes de publicidad";
        $action = "Listado de reportes de publicidad";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $data = AdvertisingReport::with(['company_advertising'])->get();
            Audith::new($id_user, $action, $request->all(), 200, compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact('data'));
    }

    public function store(Request $request)
    {
        $message = "Error al guardar el reporte de publicidad";
        $action = "Creación de reporte de publicidad";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $validated = $request->validate([
                'id_company_advertising' => 'required|integer|exists:companies_advertisings,id',
                'cant_impressions' => 'required|integer|min:0',
                'cant_clicks' => 'required|integer|min:0',
            ]);

            $data = AdvertisingReport::create($validated);

            $data->load('company_advertising.company', 'company_advertising.advertising_space', 'company_advertising.status');

            Audith::new($id_user, $action, $request->all(), 201, compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact('data'), 201);
    }

    public function update(Request $request, $id)
    {
        $message = "Error al actualizar el reporte de publicidad";
        $action = "Actualización de reporte de publicidad";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $validated = $request->validate([
                'cant_impressions' => 'sometimes|required|integer|min:0',
                'cant_clicks' => 'sometimes|required|integer|min:0',
            ]);

            $report = AdvertisingReport::findOrFail($id);
            $report->update($validated);
            $data = $report;

            $data->load('company_advertising.company', 'company_advertising.advertising_space', 'company_advertising.status');

            Audith::new($id_user, $action, $request->all(), 200, compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact('data'));
    }

    public function reportsClicks(Request $request, $id_company_advertising)
    {
        $message = "Error al registrar el click en el reporte de publicidad";
        $action = "Registro de click en reporte de publicidad";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            // Validar que la publicidad exista

            $advertising = CompanyAdvertising::findOrFail($id_company_advertising);

            if (!$advertising) {
                throw new Exception("La publicidad con ID $id_company_advertising no existe.");
            }

            // Buscar el último reporte o crear uno nuevo si no existe
            $report = AdvertisingReport::where('id_company_advertising', $id_company_advertising)->latest()->first();

            if (!$report) {
                // Crear un nuevo reporte si no existe
                $report = AdvertisingReport::create([
                    'id_company_advertising' => $id_company_advertising,
                    'cant_impressions' => 0,
                    'cant_clicks' => 0,
                ]);
            }

            // Incrementar el contador de clicks
            $report->increment('cant_clicks');
            $data = $report;

            Audith::new($id_user, $action, $request->all(), 200, compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact('data'));
    }

    public function reportsImpressions(Request $request, $id_company_advertising)
    {
        $message = "Error al registrar la impresión en el reporte de publicidad";
        $action = "Registro de impresión en reporte de publicidad";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            // Validar que la publicidad exista
            $advertising = CompanyAdvertising::findOrFail($id_company_advertising);

            if (!$advertising) {
                throw new Exception("La publicidad con ID $id_company_advertising no existe.");
            }

            // Buscar el último reporte o crear uno nuevo si no existe
            $report = AdvertisingReport::where('id_company_advertising', $id_company_advertising)->latest()->first();

            if (!$report) {
                // Crear un nuevo reporte si no existe
                $report = AdvertisingReport::create([
                    'id_company_advertising' => $id_company_advertising,
                    'cant_impressions' => 0,
                    'cant_clicks' => 0,
                ]);
            }

            // Incrementar el contador de impresiones
            $report->increment('cant_impressions');
            $data = $report;

            Audith::new($id_user, $action, $request->all(), 200, compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact('data'));
    }
}
