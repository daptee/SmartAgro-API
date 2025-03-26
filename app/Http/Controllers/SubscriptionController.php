<?php

namespace App\Http\Controllers;

use App\Models\PaymentHistory;
use App\Models\User;
use App\Models\UserPlan;
use Exception;
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
            $dollarResponse = Http::get('https://dolarapi.com/v1/dolares/oficial');

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

        $request->merge([
            'transaction_amount' => number_format($request->transaction_amount, 2, '.', ''), // Asegura 2 decimales
        ]);

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


    public function subscription_check(Request $request)
    {
        $preapprovalId = $request->query('preapproval_id');

        if (!$preapprovalId) {
            return response()->json(['error' => 'Falta el preapproval_id'], 422);
        }

        Log::info("Revisando suscripción con preapproval_id: $preapprovalId");

        $accessToken = config('app.mercadopago_token');

        // Hacemos la petición a Mercado Pago
        $preapprovalResponse = Http::withToken($accessToken)->get("https://api.mercadopago.com/preapproval/{$preapprovalId}");

        Log::info("Respuesta de Mercado Pago:", $preapprovalResponse->json());

        // Validamos que la respuesta sea exitosa
        if (!$preapprovalResponse->successful()) {
            return response()->json(['error' => 'Error al obtener la suscripción'], 400);
        }

        $subscriptionData = $preapprovalResponse->json();

        // Obtenemos el userId desde la respuesta
        $userIdSubscription = $subscriptionData['external_reference'];
        $userId = Auth::user()->id ?? null;

        Log::info($userIdSubscription);
        Log::info($userId);

        if ($subscriptionData['status'] == "failed") {
            // Si algo fallo
            return response()->json(['message' => 'Algo fallo a la hora de hacer el pago'], 401);
        }

        if ($subscriptionData['status'] == "pending") {
            // Si esta pendiente
            return response()->json(['message' => 'El pago el pago esta pendiente de aprovacion'], 404);
        }

        if (!$subscriptionData['status'] == "authorized") {
            // Si no hay autorizacion
            return response()->json(['message' => 'El pago no fue autorizado'], 404);
        }

        // Comparamos el usuario autenticado con el de la suscripción
        if ($userIdSubscription == $userId) {
            // Buscamos el último registro en UserPlan asociado al usuario
            $existingRecord = UserPlan::where('id_user', $userId)
                ->latest('created_at') // Ordenamos por la fecha más reciente
                ->first();

            $existingRecord = UserPlan::where('id_user', $userId)
                ->latest('created_at') // Ordenamos por la fecha más reciente
                ->first();

            // Verificamos si existe un registro
            if ($existingRecord) {
                // Accedemos a los datos directamente como objeto o array (sin json_decode)
                $existingData = $existingRecord->data; // Asegúrate de que 'data' sea el campo correcto

                Log::info($existingData);

                // Si $existingData es JSON almacenado como string, lo decodificamos
                $existingData = is_string($existingData) ? json_decode($existingData, true) : $existingData;

                // Validamos el ID de la preaprobación
                if ($preapprovalId == $existingData['id']) {
                    return response()->json([
                        'message' => 'Subscription encontrada',
                        'data' => $subscriptionData
                    ], 200);
                }

                return response()->json(['message' => 'El id de la subscription no coincide'], 404);
            }

            // Si no hay registro previo
            return response()->json(['message' => 'No hay registros de suscripción'], 404);
        }

        return response()->json(['error' => 'El usuario no coincide con la suscripción o algo sali mal'], 403);
    }


    public function subscription_cancel(Request $request)
    {
        $message = "Error al obtener registro";
        $userId = Auth::user()->id ?? null;
        $accessToken = config('app.mercadopago_token');
        $preapprovalId = $request->preapproval_id;

        try {
            if (!$preapprovalId) {
                return response()->json(['error' => 'Falta el preapproval_id'], 422);
            }

            // Cancelar la suscripción en Mercado Pago
            $cancelResponse = Http::withToken($accessToken)->put("https://api.mercadopago.com/preapproval/{$preapprovalId}", [
                'status' => 'cancelled'
            ]);

            if (!$cancelResponse->successful()) {
                return response()->json(['error' => 'Error al cancelar la suscripción'], 500);
            }

            // Cambiar el plan del usuario al plan gratuito (plan 1)
            $user = User::find($userId);
            if ($user) {
                $user->update(['id_plan' => 1]);
            }

            // Guardar registro en UserPlan
            UserPlan::save_history($userId, 1, ['reason' => 'Cancelación de suscripción'], now(), $preapprovalId);

            Log::info("Usuario $userId cambió al plan gratuito tras cancelar la suscripción");

            return response()->json(['message' => 'Suscripción cancelada y usuario cambiado al plan gratuito'], 200);
        } catch (Exception $e) {
            $response = ["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()];
            return response($response, 500);
        }
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
                            UserPlan::save_history($userId, 2, $subscriptionData, $subscriptionData['next_payment_date'], $this->preapprovalId);

                            Log::info('Historial guardado correctamente');
                        } else {
                            Log::warning("Registro duplicado detectado para id_user: $userId y next_payment_date: {$subscriptionData['next_payment_date']}");
                        }
                    } else {
                        Log::error("Usuario no encontrado: $userId");
                    }
                }

                if ($status == "failed") {
                    PaymentHistory::create([
                        'id_user' => $userId,
                        'type' => $status,
                        'data' => ['reason' => 'Fallo de suscripción'],
                        'preapproval_id' => $this->preapprovalId,
                        'error_message' => "Fallo de suscripción",
                    ]);
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
                    'preapproval_id' => $subscriptionData['metadata']['preapproval_id'],
                    'error_message' => null,
                ]);
            }
        }

        return response()->json(['status' => 'received']);
    }

    public function subscription_history(Request $request)
    {
        $userId = Auth::id();
        $perPage = $request->query('per_page');

        // Construir la consulta base
        $query = UserPlan::where('id_user', $userId)->orderBy('created_at', 'desc');

        // Verificar si hay paginación
        if ($perPage !== null) {
            $userPlans = $query->paginate((int) $perPage);

            $metaData = [
                'page' => $userPlans->currentPage(),
                'per_page' => $userPlans->perPage(),
                'total' => $userPlans->total(),
                'last_page' => $userPlans->lastPage(),
            ];

            $collection = $userPlans->getCollection();
        } else {
            $collection = $query->get();
            $metaData = [
                'total' => $collection->count(),
                'per_page' => 'Todos',
                'page' => 1,
                'last_page' => 1,
            ];
        }

        // Transformar los datos
        $collection->transform(function ($plan) {
            // Convertir 'data' a array si es string
            $plan->data = is_string($plan->data) ? json_decode($plan->data, true) : $plan->data;

            // Agregar historial de pagos y transformar 'data' de cada pago
            $plan->payment_history = PaymentHistory::where('preapproval_id', $plan->data['id'] ?? null)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($payment) {
                    $payment->data = is_string($payment->data) ? json_decode($payment->data, true) : $payment->data;
                    return $payment;
                });

            return $plan;
        });

        // Respuesta con datos y metadatos
        return response()->json([
            'data' => $collection,
            'meta' => $metaData,
        ]);
    }
}
