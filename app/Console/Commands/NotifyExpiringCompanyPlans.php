<?php

namespace App\Console\Commands;

use App\Services\ExpiringPlansNotificationService;
use Illuminate\Console\Command;

class NotifyExpiringCompanyPlans extends Command
{
    protected $signature = 'plans:notify-expiring';
    protected $description = 'Notifica por email los planes empresa que vencen en 30 días';

    protected $service;

    public function __construct(ExpiringPlansNotificationService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function handle()
    {
        $result = $this->service->notifyExpiring();

        $this->info("Planes encontrados por vencer en 30 días: {$result['plans_found']}");
        $this->info("Emails enviados: {$result['emails_sent']}");

        return 0;
    }
}
