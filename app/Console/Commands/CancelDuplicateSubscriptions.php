<?php

namespace App\Console\Commands;

use App\Models\UserPlan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CancelDuplicateSubscriptions extends Command
{
    protected $signature = 'subscriptions:cancel-duplicates {user_id} {keep_preapproval_id}';
    protected $description = 'Cancela en MercadoPago todas las suscripciones de un usuario excepto la indicada';

    public function handle()
    {
        $userId          = $this->argument('user_id');
        $keepPreapproval = $this->argument('keep_preapproval_id');
        $accessToken     = config('app.mercadopago_token');

        $allPreapprovals = UserPlan::where('id_user', $userId)
            ->whereNotNull('preapproval_id')
            ->orderBy('created_at', 'desc')
            ->pluck('preapproval_id')
            ->unique()
            ->values();

        if ($allPreapprovals->isEmpty()) {
            $this->warn("No se encontraron preapproval_ids para el usuario {$userId} en users_plans.");
            return 1;
        }

        $this->info("Suscripciones encontradas para usuario {$userId}: {$allPreapprovals->count()}");
        $this->info("Se conservará: {$keepPreapproval}");
        $this->newLine();

        $cancelled = 0;
        $errors    = 0;

        foreach ($allPreapprovals as $preapprovalId) {
            if ($preapprovalId === $keepPreapproval) {
                $this->line("  [SKIP] {$preapprovalId} (es la que se conserva)");
                continue;
            }

            $resp = Http::withToken($accessToken)->put(
                "https://api.mercadopago.com/preapproval/{$preapprovalId}",
                ['status' => 'cancelled']
            );

            if ($resp->successful()) {
                $this->info("  [OK]   {$preapprovalId} cancelada");
                Log::channel('mercadopago')->info("CancelDuplicateSubscriptions: preapproval {$preapprovalId} cancelada para usuario {$userId}");
                $cancelled++;
            } else {
                $body = $resp->json();
                $this->error("  [ERR]  {$preapprovalId} → HTTP {$resp->status()}: " . ($body['message'] ?? json_encode($body)));
                Log::channel('mercadopago')->warning("CancelDuplicateSubscriptions: no se pudo cancelar {$preapprovalId} para usuario {$userId}", ['response' => $body]);
                $errors++;
            }
        }

        $this->newLine();
        $this->info("Canceladas: {$cancelled} | Errores: {$errors}");

        return $errors > 0 ? 1 : 0;
    }
}
