<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PlanFinalizationService;

class FinalizeExpiredPlans extends Command
{
    protected $signature = 'plans:finalize-expired';
    protected $description = 'Finaliza los planes y publicidades que llegaron a su fecha de finalización';

    protected $service;

    public function __construct(PlanFinalizationService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function handle()
    {
        $result = $this->service->finalizeExpired();

        $this->info("Planes finalizados: {$result['plans_updated']}");
        $this->info("Publicidades finalizadas: {$result['ads_updated']}");
        $this->info("Usuarios actualizados a plan 1: {$result['users_updated']}");

        return 0;
    }
}
