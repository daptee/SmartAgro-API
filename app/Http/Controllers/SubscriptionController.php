<?php

namespace App\Http\Controllers;

use App\Mail\LowPlan;
use App\Mail\NewPayment;
use App\Mail\NotificationLowPlan;
use App\Mail\NotificationWelcomePlan;
use App\Mail\WelcomePlan;
use App\Models\PaymentHistory;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserPlan;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
                "frequency" => $request->frequency,
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
            return response()->json([
                'error' => 'Error al crear una Suscripción',
                'details' => $subscriptionResponse->json()
            ], $subscriptionResponse->status());
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
                if ($preapprovalId == $existingRecord["preapproval_id"]) {
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

            $plan = Plan::find(1);

            // Guardar registro en UserPlan
            UserPlan::save_history($userId, 1, ['reason' => 'Cancelación de suscripción', 'is_system' => 'true'], now(), $preapprovalId);

            Mail::to($user->email)->send(new LowPlan($user));
            Mail::to(config('services.research_on_demand.email'))->send(new NotificationLowPlan($user));

            Log::info("Usuario $userId cambió al plan gratuito tras cancelar la suscripción");

            return response()->json([
                'message' => 'Suscripción cancelada y usuario cambiado al plan gratuito',
                'data' => $plan
            ], 200);
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
        $mercadopagoWebhookSecret = config('app.mercadopago_webhook_secret');

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
                        if ($user->id_plan != 2) {

                            $user->update(['id_plan' => 2]);

                            Mail::to($user->email)->send(new WelcomePlan($user));
                            Mail::to(config('services.research_on_demand.email'))->send(new NotificationWelcomePlan($user));
                        }

                        $latestRecord = UserPlan::where('id_user', $userId)
                            ->where('preapproval_id', $this->preapprovalId)
                            ->orderByDesc('created_at') // o 'updated_at' si prefieres
                            ->first();

                        if ($latestRecord) {
                            $latestRecord->update([
                                'data' => $subscriptionData, // asumiendo que 'data' es un campo JSON
                                'next_payment_date' => $subscriptionData['next_payment_date'],
                            ]);
                            Log::info('Historial actualizado correctamente');
                        } else{
                            
                            UserPlan::save_history($userId, 2, $subscriptionData, $subscriptionData['next_payment_date'], $this->preapprovalId);

                            Log::info('Historial guardado correctamente');
                        } 
                    } else {
                        Log::error("Usuario no encontrado: $userId");
                    }
                }

                if ($status == "cancelled") {

                    $user = User::find($userId);
                    if ($user) {
                        $user->update(['id_plan' => 1]);
                    }

                    // Guardar registro en UserPlan
                    UserPlan::save_history($userId, 1, ['reason' => 'Cancelación de suscripción', 'is_system' => 'false'], now(), $this->preapprovalId);

                    Mail::to($user->email)->send(new LowPlan($user));
                    Mail::to(config('services.research_on_demand.email'))->send(new NotificationLowPlan($user));


                    return response()->json(['message' => 'Pago fallido registrado'], 200);
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


                if ($data['action'] == 'payment.updated') {
                    Log::info("Actualizando pago para preapproval_id: {$subscriptionData['metadata']['preapproval_id']}");

                    // Buscar el último PaymentHistory que coincida
                    $paymentHistory = PaymentHistory::where('preapproval_id', $subscriptionData['metadata']['preapproval_id'])
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if ($paymentHistory) {
                        // Actualizar los datos
                        $paymentHistory->update([
                            'data' => json_encode($subscriptionData), // Actualizas el campo `data`
                            'error_message' => null, // Si quieres limpiar el error o actualizarlo
                        ]);

                        Log::info("PaymentHistory actualizado correctamente para id: {$paymentHistory->id}");
                    } else {
                        Log::warning("No se encontró PaymentHistory para preapproval_id: {$subscriptionData['metadata']['preapproval_id']}");
                    }

                    return response()->json(['status' => 'payment updated']);
                }

                PaymentHistory::create([
                    'id_user' => $userId,
                    'type' => $data['type'],
                    'data' => json_encode($subscriptionData),
                    'preapproval_id' => $subscriptionData['metadata']['preapproval_id'],
                    'error_message' => null,
                ]);

                $user = User::find($userId);

                if ($user) {
                    Mail::to($user->email)->send(new NewPayment($user));
                }
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
                ->orderBy('id', 'desc')
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

    public function subscription_plan(Request $request)
    {
        $userId = Auth::id();
        $perPage = $request->query('per_page');

        // Construir la consulta base: Obtener historial de pagos primero
        $query = PaymentHistory::where('id_user', $userId)->orderBy('created_at', 'desc');

        // Verificar si hay paginación
        if ($perPage !== null) {
            $paymentHistory = $query->paginate((int) $perPage);

            $metaData = [
                'page' => $paymentHistory->currentPage(),
                'per_page' => $paymentHistory->perPage(),
                'total' => $paymentHistory->total(),
                'last_page' => $paymentHistory->lastPage(),
            ];

            $collection = $paymentHistory->getCollection();
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
        $collection->transform(function ($payment) {
            // Convertir 'data' a array si es string
            $payment->data = is_string($payment->data) ? json_decode($payment->data, true) : $payment->data;

            // Obtener el último UserPlan asociado al preapproval_id
            $latestUserPlan = UserPlan::where('preapproval_id', $payment->preapproval_id)
                ->orderBy('id', 'desc')
                ->first();

            if ($latestUserPlan) {
                $payment->user_plan = [
                    'id' => $latestUserPlan->id,
                ];
            } else {
                $payment->user_plan = null;
            }

            return $payment;
        });


        // Respuesta con datos y metadatos
        return response()->json([
            'data' => $collection,
            'meta' => $metaData,
        ]);
    }

    public function cronPayment(Request $request)
    {
        $plan = Plan::find(2);
        $pricesUSD = $plan['price']['frecuency'];

        $dollarResponse = Http::get('https://dolarapi.com/v1/dolares/oficial');
        $dollarData = $dollarResponse->json();
        $dollarRate = $dollarData['venta'] ?? null;

        if (!$dollarRate) {
            return response()->json(['error' => 'No se pudo obtener el valor del dólar'], 500);
        }

        $priceMonthly = round($pricesUSD['monthly']['price'] * $dollarRate, 2);
        $priceYearly = round($pricesUSD['yearly']['price'] * $dollarRate, 2);

        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();

        $accessToken = config('app.mercadopago_token');

        $userPlans = UserPlan::where('id_plan', 2)
            ->whereDate('next_payment_date', '>=', $today)
            ->whereDate('next_payment_date', '<=', $tomorrow)
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($plan) use ($priceMonthly, $priceYearly, $accessToken) {
                $data = json_decode($plan->data, true);
                $frequency = $data['auto_recurring']['frequency'] ?? null;
                $status = $data['status'] ?? null;
                $preapprovalId = $plan->preapproval_id;

                if ($frequency && $preapprovalId && $status != "cancelled" && $status) {
                    $newAmount = $frequency == 1 ? $priceMonthly : $priceYearly;

                    $payload = [
                        'auto_recurring' => [
                            'transaction_amount' => $newAmount
                        ]
                    ];

                    // PUT request to MercadoPago
                    $response = Http::withToken($accessToken)
                        ->put("https://api.mercadopago.com/preapproval/{$preapprovalId}", $payload);

                    if ($response->successful()) {
                        // Guardamos la nueva data devuelta por MercadoPago
                        $newData = $response->json();
                        $plan->data = json_encode($newData);
                        $plan->save();
                    } else {
                        // Si falló, podrías loguear o manejar el error
                        $data['update_error'] = $response->json();
                    }
                }

                // Devolver la data ya actualizada o con error
                return [
                    'id' => $plan->id,
                    'preapproval_id' => $plan->preapproval_id,
                    'data' => json_decode($plan->data, true),
                ];
            });

        return response()->json([
            'data' => $userPlans,
        ]);
    }

}
