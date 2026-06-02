<?php

namespace App\Services;

use App\Mail\ExpiringCompanyPlanNotification;
use App\Models\CompanyPlan;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ExpiringPlansNotificationService
{
    public function notifyExpiring(): array
    {
        $targetDate = Carbon::today()->addDays(30);

        $plans = CompanyPlan::with('company')
            ->whereDate('date_end', $targetDate)
            ->where('status_id', 1)
            ->get();

        if ($plans->isEmpty()) {
            Log::info('ExpiringPlansNotificationService: No hay planes de empresa que venzan en 30 días.');
            return [
                'plans_found' => 0,
                'emails_sent' => 0,
            ];
        }

        $recipients = [
            'comercial@smartagro.io',
            'info@smartagro.io',
        ];

        $emailsSent = 0;

        foreach ($recipients as $email) {
            try {
                Mail::to($email)->send(new ExpiringCompanyPlanNotification($plans));
                Log::info("ExpiringPlansNotificationService: Email enviado a {$email} con {$plans->count()} plan(es) por vencer.");
                $emailsSent++;
            } catch (Exception $e) {
                Log::error("ExpiringPlansNotificationService: Error al enviar email a {$email}", [
                    'error' => $e->getMessage(),
                    'line'  => $e->getLine(),
                ]);
            }
        }

        return [
            'plans_found' => $plans->count(),
            'emails_sent' => $emailsSent,
        ];
    }
}
