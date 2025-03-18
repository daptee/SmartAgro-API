<?php

namespace App\Http\Controllers;

use App\Models\PaymentHistory;
use App\Models\User;
use App\Models\UserPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    public function subscription(Request $request)
    {
        $user_id = Auth::user()->id;
        $accessToken = config('app.mercadopago_token');

        // Verificar si la moneda es USD y convertir a ARS
        if (strtolower($request->currency) === 'usd') {
            $dollarResponse = Http::get('https://dolarapi.com/v1/dolares/blue');

            if ($dollarResponse->successful()) {
                $dollarData = $dollarResponse->json();
                $exchangeRate = $dollarData['venta'];
                $request->merge([
                    'transaction_amount' => $request->transaction_amount * $exchangeRate,
                    'currency' => 'ARS'
                ]);
            } else {
                return response()->json(['error' => 'Error al obtener la tasa de cambio'], 500);
            }
        } elseif (strtolower($request->currency) !== 'ars') {
            return response()->json(['error' => 'Moneda no soportada'], 400);
        }

        // Crear el Subscription
        $subscriptionResponse = Http::withToken($accessToken)->post('https://api.mercadopago.com/preapproval', [
            "auto_recurring" => [
                "frequency" => 1,
                "frequency_type" => $request->frequency_type,
                "transaction_amount" => $request->transaction_amount,
                "currency_id" => "ARS"
            ],
            "payer_email" => $request->payer_email,
            "external_reference" => $user_id,
            "back_url" => $request->back_url,
            "reason" => $request->reason,
            "status" => "pending"
        ]);

        if (!$subscriptionResponse->successful()) {
            return response()->json(['error' => 'Error al crear una Suscripción'], 500);
        }
        // Retornar el link de pago
        return response()->json([
            'message' => 'Suscripción creada con éxito',
            'init_point' => $subscriptionResponse->json('init_point')
        ]);
    }


    private $preapprovalId;

    public function handleWebhook(Request $request)
    {
        $data = $request->all();

        Log::info('Webhook recibido de Mercado Pago:', $data);

        $accessToken = config('app.mercadopago_token');

        // 🔥 Guardamos temporalmente el preapprovalId si es subscription_preapproval
        if (isset($data['type']) && $data['type'] == 'subscription_preapproval') {
            $this->preapprovalId = $data['data']['id'];

            $preapprovalResponse = Http::withToken($accessToken)->get("https://api.mercadopago.com/preapproval/{$this->preapprovalId}");

            if ($preapprovalResponse->successful()) {
                $subscriptionData = $preapprovalResponse->json();
                $status = $subscriptionData['status'];
                $userId = json_decode($subscriptionData['external_reference'], true);

                if ($status == "authorized") {

                    $user = User::find($userId);

                    if ($user) {
                        $user->update(['id_plan' => 2]);

                        $existingRecord = UserPlan::where('id_user', $userId)
                            ->where('next_payment_date', $subscriptionData['next_payment_date'])
                            ->first();

                        if (!$existingRecord) {
                            UserPlan::save_history($userId, 2, $subscriptionData, $subscriptionData['next_payment_date']);

                            Log::info('Historial guardado correctamente');
                        } else {
                            Log::warning("Registro duplicado detectado para id_user: $userId y next_payment_date: {$subscriptionData['next_payment_date']}");
                        }
                    } else {
                        Log::error("Usuario no encontrado: $userId");
                    }
                }

                if ($status == "failed") {
                    return response()->json(['message' => 'Pago fallido registrado'], 200);
                }
            }
        }

        // 🔥 Manejo de pagos individuales autorizados
        if (isset($data['type']) && $data['type'] == 'payment') {
            $this->preapprovalId = $data['data']['id'];

            Log::info('id preapprovalId: ' . $this->preapprovalId);

            $preapprovalResponse = Http::withToken($accessToken)->get("https://api.mercadopago.com/v1/payments/{$this->preapprovalId}");

            if ($preapprovalResponse->successful()) {
                $subscriptionData = $preapprovalResponse->json();
                $status = $subscriptionData['status'];
                $userId = json_decode($subscriptionData['external_reference'], true);

                PaymentHistory::create([
                    'id_user' => $userId,
                    'type' => $data['type'],
                    'data' => json_encode($subscriptionData),
                    'error_message' => null,
                ]);
            }
        }

        return response()->json(['status' => 'received']);
    }

}
