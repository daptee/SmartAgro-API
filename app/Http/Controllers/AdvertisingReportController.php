<?php

namespace App\Http\Controllers;

use App\Models\AdvertisingInteraction;
use App\Models\Audith;
use App\Models\CompanyAdvertising;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            // Obtener estadísticas agregadas desde advertising_interactions
            $advertisings = CompanyAdvertising::with(['company', 'advertising_space', 'status'])
                ->get()
                ->map(function ($advertising) {
                    $impressions = AdvertisingInteraction::forCompanyAdvertising($advertising->id)
                        ->impressions()
                        ->count();

                    $clicks = AdvertisingInteraction::forCompanyAdvertising($advertising->id)
                        ->clicks()
                        ->count();

                    return [
                        'id' => $advertising->id,
                        'company_advertising' => $advertising,
                        'cant_impressions' => $impressions,
                        'cant_clicks' => $clicks,
                        'created_at' => $advertising->created_at,
                        'updated_at' => $advertising->updated_at,
                    ];
                });

            $data = $advertisings;
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
            $advertising = CompanyAdvertising::find($id_company_advertising);

            if (!$advertising) {
                throw new Exception("La publicidad con ID $id_company_advertising no existe.");
            }

            // Registrar la interacción individual
            $contextData = [
                'user_agent' => $request->header('User-Agent'),
                'ip_address' => $request->ip(),
                'referrer' => $request->header('Referer'),
            ];

            // Agregar datos adicionales del request si existen
            if ($request->has('device_type')) {
                $contextData['device_type'] = $request->get('device_type');
            }

            if ($request->has('additional_data')) {
                $contextData['additional_data'] = $request->get('additional_data');
            }

            $interaction = AdvertisingInteraction::recordInteraction(
                'click',
                $id_company_advertising,
                null,
                $id_user,
                $contextData
            );

            // Obtener estadísticas actualizadas
            $totalClicks = AdvertisingInteraction::forCompanyAdvertising($id_company_advertising)
                ->clicks()
                ->count();

            $totalImpressions = AdvertisingInteraction::forCompanyAdvertising($id_company_advertising)
                ->impressions()
                ->count();

            $data = [
                'interaction' => $interaction,
                'statistics' => [
                    'cant_clicks' => $totalClicks,
                    'cant_impressions' => $totalImpressions,
                ],
            ];

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
            $advertising = CompanyAdvertising::find($id_company_advertising);

            if (!$advertising) {
                throw new Exception("La publicidad con ID $id_company_advertising no existe.");
            }

            // Registrar la interacción individual
            $contextData = [
                'user_agent' => $request->header('User-Agent'),
                'ip_address' => $request->ip(),
                'referrer' => $request->header('Referer'),
            ];

            // Agregar datos adicionales del request si existen
            if ($request->has('device_type')) {
                $contextData['device_type'] = $request->get('device_type');
            }

            if ($request->has('additional_data')) {
                $contextData['additional_data'] = $request->get('additional_data');
            }

            $interaction = AdvertisingInteraction::recordInteraction(
                'impression',
                $id_company_advertising,
                null,
                $id_user,
                $contextData
            );

            // Obtener estadísticas actualizadas
            $totalImpressions = AdvertisingInteraction::forCompanyAdvertising($id_company_advertising)
                ->impressions()
                ->count();

            $totalClicks = AdvertisingInteraction::forCompanyAdvertising($id_company_advertising)
                ->clicks()
                ->count();

            $data = [
                'interaction' => $interaction,
                'statistics' => [
                    'cant_impressions' => $totalImpressions,
                    'cant_clicks' => $totalClicks,
                ],
            ];

            Audith::new($id_user, $action, $request->all(), 200, compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact('data'));
    }

    /**
     * Obtener historial detallado de interacciones para una publicidad
     */
    public function getInteractionHistory(Request $request, $id_company_advertising)
    {
        $message = "Error al obtener el historial de interacciones";
        $action = "Obtención de historial de interacciones de publicidad";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            // Validar que la publicidad exista
            $advertising = CompanyAdvertising::find($id_company_advertising);

            if (!$advertising) {
                throw new Exception("La publicidad con ID $id_company_advertising no existe.");
            }

            // Parámetros de paginación
            $perPage = $request->get('per_page', 50);
            $page = $request->get('page', 1);

            // Filtros opcionales
            $interactionType = $request->get('interaction_type'); // 'impression' o 'click'
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');
            $userId = $request->get('user_id');

            // Query base
            $query = AdvertisingInteraction::forCompanyAdvertising($id_company_advertising)
                ->with(['user:id,name,last_name,email']);

            // Aplicar filtros
            if ($interactionType) {
                $query->ofType($interactionType);
            }

            if ($dateFrom) {
                $query->where('created_at', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->where('created_at', '<=', $dateTo);
            }

            if ($userId) {
                $query->where('user_id', $userId);
            }

            // Obtener interacciones paginadas
            $interactionsPaginated = $query->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            // Crear meta de paginación
            $meta = [
                'page' => $interactionsPaginated->currentPage(),
                'per_page' => $interactionsPaginated->perPage(),
                'total' => $interactionsPaginated->total(),
                'last_page' => $interactionsPaginated->lastPage(),
            ];

            // Obtener solo los items
            $interactions = $interactionsPaginated->items();

            // Obtener estadísticas agregadas con los mismos filtros
            $statsQuery = AdvertisingInteraction::forCompanyAdvertising($id_company_advertising);

            if ($interactionType) {
                $statsQuery->ofType($interactionType);
            }

            if ($dateFrom) {
                $statsQuery->where('created_at', '>=', $dateFrom);
            }

            if ($dateTo) {
                $statsQuery->where('created_at', '<=', $dateTo);
            }

            if ($userId) {
                $statsQuery->where('user_id', $userId);
            }

            $totalImpressions = (clone $statsQuery)->impressions()->count();
            $totalClicks = (clone $statsQuery)->clicks()->count();
            $uniqueUsers = (clone $statsQuery)->distinct('user_id')
                ->whereNotNull('user_id')
                ->count('user_id');

            // CTR (Click-Through Rate)
            $ctr = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0;

            // Estadísticas por fecha
            $statsByDate = AdvertisingInteraction::forCompanyAdvertising($id_company_advertising)
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('interaction_type'),
                    DB::raw('COUNT(*) as total'),
                    DB::raw('COUNT(DISTINCT user_id) as unique_users')
                )
                ->when($dateFrom, function ($q) use ($dateFrom) {
                    return $q->where('created_at', '>=', $dateFrom);
                })
                ->when($dateTo, function ($q) use ($dateTo) {
                    return $q->where('created_at', '<=', $dateTo);
                })
                ->groupBy(DB::raw('DATE(created_at)'), 'interaction_type')
                ->orderBy(DB::raw('DATE(created_at)'), 'desc')
                ->get();

            // Top usuarios (más interacciones)
            $topUsers = AdvertisingInteraction::forCompanyAdvertising($id_company_advertising)
                ->select('user_id', DB::raw('COUNT(*) as total_interactions'))
                ->whereNotNull('user_id')
                ->when($dateFrom, function ($q) use ($dateFrom) {
                    return $q->where('created_at', '>=', $dateFrom);
                })
                ->when($dateTo, function ($q) use ($dateTo) {
                    return $q->where('created_at', '<=', $dateTo);
                })
                ->groupBy('user_id')
                ->orderBy('total_interactions', 'desc')
                ->limit(10)
                ->with(['user:id,name,last_name,email'])
                ->get();

            $data = [
                'advertising' => $advertising->load(['company', 'advertising_space', 'status']),
                'interactions' => $interactions,
                'meta' => $meta,
                'statistics' => [
                    'totals' => [
                        'impressions' => $totalImpressions,
                        'clicks' => $totalClicks,
                        'unique_users' => $uniqueUsers,
                        'ctr_percentage' => $ctr,
                    ],
                    'by_date' => $statsByDate,
                    'top_users' => $topUsers,
                ],
            ];

            Audith::new($id_user, $action, $request->all(), 200, compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact('data'));
    }
}
