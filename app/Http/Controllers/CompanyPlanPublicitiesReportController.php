<?php

namespace App\Http\Controllers;

use App\Models\CompanyPlanPublicityReport;
use App\Models\Audith;
use App\Models\CompanyPlanPublicity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class CompanyPlanPublicitiesReportController extends Controller
{
    public function index(Request $request)
    {
        $message = "Error al obtener los reportes de publicidad del plan empresa";
        $action = "Listado de reportes de publicidad del plan empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $data = CompanyPlanPublicityReport::with(['company_plan_publicity'])->get();
            Audith::new($id_user, $action, $request->all(), 200, compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact('data'));
    }

    public function store(Request $request)
    {
        $message = "Error al guardar el reporte de publicidad del plan empresa";
        $action = "Creación de reporte de publicidad del plan empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $validated = $request->validate([
                'id_company_plan_publicity' => 'required|integer|exists:company_plan_publicities,id',
                'cant_impressions' => 'required|integer|min:0',
                'cant_clicks' => 'required|integer|min:0',
            ]);

            $data = CompanyPlanPublicityReport::create($validated);

            $data->load('company_plan_publicity.plan', 'company_plan_publicity.advertisingSpace');

            Audith::new($id_user, $action, $request->all(), 201, compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact('data'), 201);
    }

    public function update(Request $request, $id)
    {
        $message = "Error al actualizar el reporte de publicidad del plan empresa";
        $action = "Actualización de reporte de publicidad del plan empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $validated = $request->validate([
                'cant_impressions' => 'sometimes|required|integer|min:0',
                'cant_clicks' => 'sometimes|required|integer|min:0',
            ]);

            $report = CompanyPlanPublicityReport::findOrFail($id);
            $report->update($validated);
            $data = $report;

            $data->load('company_plan_publicity.plan', 'company_plan_publicity.advertisingSpace');

            Audith::new($id_user, $action, $request->all(), 200, compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact('data'));
    }

    public function reportsClicks(Request $request, $id_company_plan_publicity)
    {
        $message = "Error al registrar el click en el reporte de publicidad del plan empresa";
        $action = "Registro de click en reporte de publicidad del plan empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            // Validar que la publicidad exista

            $advertising = CompanyPlanPublicity::findOrFail($id_company_plan_publicity);

            if (!$advertising) {
                throw new Exception("La publicidad con ID $id_company_plan_publicity no existe.");
            }

            // Buscar el último reporte o crear uno nuevo si no existe
            $report = CompanyPlanPublicityReport::where('id_company_plan_publicity', $id_company_plan_publicity)->latest()->first();

            if (!$report) {
                // Crear un nuevo reporte si no existe
                $report = CompanyPlanPublicityReport::create([
                    'id_company_plan_publicity' => $id_company_plan_publicity,
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

    public function reportsImpressions(Request $request, $id_company_plan_publicity)
    {
        $message = "Error al registrar la impresión en el reporte de publicidad del plan empresa";
        $action = "Registro de impresión en reporte de publicidad del plan empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            // Validar que la publicidad exista
            $advertising = CompanyPlanPublicity::findOrFail($id_company_plan_publicity);

            if (!$advertising) {
                throw new Exception("La publicidad con ID $id_company_plan_publicity no existe.");
            }

            // Buscar el último reporte o crear uno nuevo si no existe
            $report = CompanyPlanPublicityReport::where('id_company_plan_publicity', $id_company_plan_publicity)->latest()->first();

            if (!$report) {
                // Crear un nuevo reporte si no existe
                $report = CompanyPlanPublicityReport::create([
                    'id_company_plan_publicity' => $id_company_plan_publicity,
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
