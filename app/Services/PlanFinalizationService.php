<?php

namespace App\Services;

use App\Models\CompanyPlan;
use App\Models\CompanyAdvertising;
use App\Models\CompanyInvitation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PlanFinalizationService
{
    public function finalizeExpired()
    {
        $today = Carbon::today();

        // Finalizar planes de empresa
        $plansUpdated = CompanyPlan::whereDate('date_end', '<=', $today)
            ->where('status_id', '!=', 4)
            ->update(['status_id' => 4]);

        // Finalizar publicidades
        $adsUpdated = CompanyAdvertising::whereDate('date_end', '<=', $today)
            ->where('id_advertising_status', '!=', 3)
            ->update(['id_advertising_status' => 3]);

        // Actualizar usuarios que pertenecen a planes vencidos
        $emails = CompanyInvitation::whereIn('id_company_plan', function($q) use ($today) {
                $q->select('id')
                  ->from('company_plans')
                  ->whereDate('date_end', '<=', $today)
                  ->where('status_id', 4);
            })
            ->where('status_id', 2)
            ->pluck('mail');
        

            // solo actualiza usuarios con plan 3
        $usersUpdated = User::whereIn('email', $emails)
            ->where('id_plan', 3)
            ->update(['id_plan' => 1]);

        return [
            'plans_updated' => $plansUpdated,
            'ads_updated' => $adsUpdated,
            'users_updated' => $usersUpdated,
        ];
    }
}
