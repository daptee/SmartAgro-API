<?php

namespace App\Http\Controllers;

use App\Mail\ExpiredSubscription;
use App\Mail\FailedPayment;
use App\Mail\LowPlan;
use App\Mail\NewPayment;
use App\Mail\NotificationExpiredSubscription;
use App\Mail\NotificationFailedPayment;
use App\Mail\NotificationLowPlan;
use App\Mail\NotificationWelcomePlan;
use App\Mail\WelcomePlan;
use App\Models\Audith;
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
    /**
     * Envía un email de forma segura con manejo de errores
     */
    private function sendEmailSafely($email, $mailable, $context = '')
    {
        if (!$email) {
            Log::warning("Intento de enviar email sin destinatario. Contexto: $context");
            return false;
        }

        try {
            Mail::to($email)->send($mailable);
            Log::info("Email enviado exitosamente a $email. Contexto: $context");
            return true;
        } catch (Exception $e) {
            Log::error("Error al enviar email a $email. Contexto: $context", [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
            return false;
        }
    }

    /**
     * Valida la firma del webhook de MercadoPago
     */
    private function validateWebhookSignature($data, $xSignature, $xRequestId, $secret)
    {
        if (!$xSignature || !$xRequestId) {
            return false;
        }

        // Separar la firma en sus partes
        $parts = explode(',', $xSignature);
        $ts = null;
        $hash = null;

        foreach ($parts as $part) {
            $keyValue = explode('=', $part, 2);
            if (count($keyValue) === 2) {
                if (trim($keyValue[0]) === 'ts') {
                    $ts = trim($keyValue[1]);
                } elseif (trim($keyValue[0]) === 'v1') {
                    $hash = trim($keyValue[1]);
                }
            }
        }

        if (!$ts || !$hash) {
            return false;
        }

        // Construir el manifest (según documentación de MercadoPago)
        $manifest = "id:{$xRequestId};request-id:{$xRequestId};ts:{$ts};";

        // Generar la firma esperada
        $expectedHash = hash_hmac('sha256', $manifest, $secret);

        // Comparar las firmas
        return hash_equals($expectedHash, $hash);
    }

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
            'transaction_amount' => number_format($request->transaction_amount, 2, '.', ''),
        ]);

        // Preparar cuerpo de la solicitud a MercadoPago
        $subscriptionPayload = [
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
        ];

        // Agregar mes gratis si corresponde
        if (
            strtolower($request->frequency_type) === 'months' &&
            intval($request->frequency) === 1 &&
            $request->has('free_trial') &&
            filter_var($request->free_trial, FILTER_VALIDATE_BOOLEAN)
        ) {
            $subscriptionPayload['auto_recurring']['free_trial'] = [
                "frequency" => 1,
                "frequency_type" => "months"
            ];
        }

        // Enviar la solicitud
        $subscriptionResponse = Http::withToken($accessToken)->post(
            'https://api.mercadopago.com/preapproval',
            $subscriptionPayload
        );

        if (!$subscriptionResponse->successful()) {
            return response()->json([
                'error' => 'Error al crear una Suscripción',
                'details' => $subscriptionResponse->json()
            ], $subscriptionResponse->status());
        }

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

        Log::channel('mercadopago')->info("Revisando suscripción con preapproval_id: $preapprovalId");

        $accessToken = config('app.mercadopago_token');

        // Hacemos la petición a Mercado Pago
        $preapprovalResponse = Http::withToken($accessToken)->get("https://api.mercadopago.com/preapproval/{$preapprovalId}");

        Log::channel('mercadopago')->info("Respuesta de Mercado Pago:", $preapprovalResponse->json());

        // Validamos que la respuesta sea exitosa
        if (!$preapprovalResponse->successful()) {
            return response()->json(['error' => 'Error al obtener la suscripción'], 400);
        }

        $subscriptionData = $preapprovalResponse->json();

        // Obtenemos el userId desde la respuesta
        $userIdSubscription = $subscriptionData['external_reference'];
        $userId = Auth::user()->id ?? null;

        Log::channel('mercadopago')->info($userIdSubscription);
        Log::channel('mercadopago')->info($userId);

        if ($subscriptionData['status'] == "failed") {
            // Si algo fallo
            return response()->json(['message' => 'Algo fallo a la hora de hacer el pago'], 401);
        }

        if ($subscriptionData['status'] == "pending") {
            // Si esta pendiente
            return response()->json(['message' => 'El pago el pago esta pendiente de aprovacion'], 404);
        }

        if ($subscriptionData['status'] != "authorized") {
            // Si no hay autorizacion
            return response()->json(['message' => 'El pago no fue autorizado'], 404);
        }

        // Comparamos el usuario autenticado con el de la suscripción
        if ($userIdSubscription == $userId) {
            // Buscamos el último registro en UserPlan asociado al usuario
            $existingRecord = UserPlan::where('id_user', $userId)
                ->latest('created_at') // Ordenamos por la fecha más reciente
                ->first();

            // Verificamos si existe un registro
            if ($existingRecord) {
                // Accedemos a los datos directamente como objeto o array (sin json_decode)
                $existingData = $existingRecord->data; // Asegúrate de que 'data' sea el campo correcto

                Log::channel('mercadopago')->info($existingData);

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
            if (!$user) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }

            $user->update([
                'id_plan' => 1,
                'is_debtor' => false  // Ya no es deudor porque canceló la suscripción
            ]);

            $plan = Plan::find(1);

            // Guardar registro en UserPlan
            UserPlan::save_history($userId, 1, ['reason' => 'Cancelación de suscripción', 'is_system' => 'true'], now(), $preapprovalId);

            // Enviar emails de forma segura
            $this->sendEmailSafely($user->email, new LowPlan($user), 'Cancelación manual de suscripción - usuario');
            $this->sendEmailSafely(config('services.research_on_demand.email'), new NotificationLowPlan($user), 'Cancelación manual de suscripción - admin');

            Log::channel('mercadopago')->info("Usuario $userId cambió al plan gratuito tras cancelar la suscripción");

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

        Log::channel('mercadopago')->info('Webhook recibido de Mercado Pago:', $data);

        $accessToken = config('app.mercadopago_token');

        // Validación de firma deshabilitada temporalmente
        // $mercadopagoWebhookSecret = config('app.mercadopago_webhook_secret');
        // if ($mercadopagoWebhookSecret) {
        //     $xSignature = $request->header('x-signature');
        //     $xRequestId = $request->header('x-request-id');
        //     if (!$this->validateWebhookSignature($data, $xSignature, $xRequestId, $mercadopagoWebhookSecret)) {
        //         Log::channel('mercadopago')->warning('Webhook rechazado: firma inválida', [
        //             'x-signature' => $xSignature,
        //             'x-request-id' => $xRequestId
        //         ]);
        //         return response()->json(['error' => 'Invalid signature'], 401);
        //     }
        // }

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

                            // Enviar emails de forma segura
                            $this->sendEmailSafely($user->email, new WelcomePlan($user), 'Suscripción autorizada - usuario');
                            $this->sendEmailSafely(config('services.research_on_demand.email'), new NotificationWelcomePlan($user), 'Suscripción autorizada - admin');
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
                            Log::channel('mercadopago')->info('Historial actualizado correctamente');
                        } else {

                            UserPlan::save_history($userId, 2, $subscriptionData, $subscriptionData['next_payment_date'], $this->preapprovalId);

                            Log::channel('mercadopago')->info('Historial guardado correctamente');

                            if ($subscriptionData['auto_recurring']['free_trial'] ?? false) {
                                // Log completo de la suscripción
                                Log::channel('mercadopago')->info('Datos de suscripción recibidos:', $subscriptionData);
                                Log::channel('mercadopago')->info('Mes gratis aplicado correctamente');
                                PaymentHistory::create([
                                    'id_user' => $userId,
                                    'type' => 'free_trial',
                                    'data' => json_encode($subscriptionData),
                                    'preapproval_id' => $subscriptionData['id'],
                                    'error_message' => "Primer mes gratuito aplicado",
                                ]);
                            } else {
                                Log::channel('mercadopago')->info('Mes gratis no aplicado');
                            }
                        }
                    } else {
                        Log::channel('mercadopago')->error("Usuario no encontrado: $userId");
                    }
                }

                if ($status == "cancelled") {

                    $user = User::find($userId);
                    if ($user) {
                        $user->update([
                            'id_plan' => 1,
                            'is_debtor' => false  // Ya no es deudor porque se canceló la suscripción
                        ]);

                        // Guardar registro en UserPlan
                        UserPlan::save_history($userId, 1, ['reason' => 'Cancelación de suscripción', 'is_system' => 'false'], now(), $this->preapprovalId);

                        // Enviar emails de forma segura
                        $this->sendEmailSafely($user->email, new LowPlan($user), 'Webhook cancelación - usuario');
                        $this->sendEmailSafely(config('services.research_on_demand.email'), new NotificationLowPlan($user), 'Webhook cancelación - admin');
                    } else {
                        Log::channel('mercadopago')->error("Usuario no encontrado al procesar cancelación: $userId");
                    }

                    return response()->json(['message' => 'Suscripción cancelada'], 200);
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

                if ($status == "paused") {
                    // Suscripción pausada por MercadoPago después de múltiples intentos fallidos
                    $user = User::find($userId);

                    if ($user) {
                        // Verificar si ya usó su período de gracia
                        if ($user->grace_period_used) {
                            // Ya usó su período de gracia, bajar a plan gratuito inmediatamente
                            Log::channel('mercadopago')->warning("Suscripción pausada para usuario $userId que ya usó su período de gracia. Bajando a plan gratuito.");

                            $user->update([
                                'id_plan' => 1,
                                'is_debtor' => false
                            ]);

                            UserPlan::save_history($userId, 1, ['reason' => 'Suscripción pausada - período de gracia ya utilizado', 'is_system' => 'false'], now(), $this->preapprovalId);

                            // Enviar emails
                            $this->sendEmailSafely($user->email, new ExpiredSubscription($user), 'Webhook suscripción pausada sin gracia - usuario');
                            $this->sendEmailSafely(config('services.research_on_demand.email'), new NotificationExpiredSubscription($user), 'Webhook suscripción pausada sin gracia - admin');

                            PaymentHistory::create([
                                'id_user' => $userId,
                                'type' => $status,
                                'data' => $subscriptionData,
                                'preapproval_id' => $this->preapprovalId,
                                'error_message' => "Suscripción pausada - Usuario bajado a plan gratuito (período de gracia ya usado)",
                            ]);

                            return response()->json(['message' => 'Suscripción pausada y usuario bajado a plan gratuito'], 200);
                        } else {
                            // Primera vez pausada, mantener plan (período de gracia)
                            Log::channel('mercadopago')->info("Suscripción pausada para usuario $userId. Otorgando período de gracia (manteniendo Plan Siembra).");

                            // Marcar que usó su período de gracia
                            $user->update(['grace_period_used' => true]);

                            PaymentHistory::create([
                                'id_user' => $userId,
                                'type' => $status,
                                'data' => $subscriptionData,
                                'preapproval_id' => $this->preapprovalId,
                                'error_message' => "Suscripción pausada - Período de gracia otorgado (se intentará cobrar en próximo ciclo)",
                            ]);

                            return response()->json(['message' => 'Suscripción pausada - período de gracia otorgado'], 200);
                        }
                    } else {
                        Log::channel('mercadopago')->error("Usuario no encontrado al procesar suscripción pausada: $userId");

                        PaymentHistory::create([
                            'id_user' => $userId,
                            'type' => $status,
                            'data' => $subscriptionData,
                            'preapproval_id' => $this->preapprovalId,
                            'error_message' => "Suscripción pausada - Usuario no encontrado",
                        ]);

                        return response()->json(['message' => 'Suscripción pausada pero usuario no encontrado'], 200);
                    }
                }
            }
        }

        // 🔥 Manejo de pagos individuales autorizados
        if (isset($data['type']) && $data['type'] == 'payment') {
            $this->preapprovalId = $data['data']['id'];

            Log::channel('mercadopago')->info('id preapprovalId: ' . $this->preapprovalId);

            $preapprovalResponse = Http::withToken($accessToken)->get("https://api.mercadopago.com/v1/payments/{$this->preapprovalId}");

            if ($preapprovalResponse->successful()) {
                $subscriptionData = $preapprovalResponse->json();
                $status = $subscriptionData['status'];
                $userId = json_decode($subscriptionData['external_reference'], true);


                if ($data['action'] == 'payment.updated') {
                    Log::channel('mercadopago')->info("Actualizando pago para preapproval_id: {$subscriptionData['metadata']['preapproval_id']}");

                    // Buscar el último PaymentHistory que coincida
                    $paymentHistory = PaymentHistory::where('preapproval_id', $subscriptionData['point_of_interaction']['transaction_data']['subscription_id'])
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if ($paymentHistory) {
                        // Actualizar los datos
                        $paymentHistory->update([
                            'data' => json_encode($subscriptionData), // Actualizas el campo `data`
                            'error_message' => null, // Si quieres limpiar el error o actualizarlo
                        ]);

                        Log::channel('mercadopago')->info("PaymentHistory actualizado correctamente para id: {$paymentHistory->id}");
                    } else {
                        Log::channel('mercadopago')->warning("No se encontró PaymentHistory para preapproval_id: {$subscriptionData['metadata']['preapproval_id']}");
                    }

                    return response()->json(['status' => 'payment updated']);
                }

                // Guardar el pago en el historial (evitar duplicados por webhooks simultáneos)
                $preapprovalIdForHistory = $subscriptionData['point_of_interaction']['transaction_data']['subscription_id'] ?? $subscriptionData['metadata']['preapproval_id'];

                // Verificar si ya existe un registro reciente del mismo pago
                $existingPayment = PaymentHistory::where('id_user', $userId)
                    ->where('preapproval_id', $preapprovalIdForHistory)
                    ->where('type', $status)
                    ->where('created_at', '>=', now()->subMinutes(5))
                    ->first();

                if (!$existingPayment) {
                    PaymentHistory::create([
                        'id_user' => $userId,
                        'type' => $status,
                        'data' => json_encode($subscriptionData),
                        'preapproval_id' => $preapprovalIdForHistory,
                        'error_message' => ($status == 'approved' || $status == 'authorized') ? null : ($subscriptionData['status_detail'] ?? 'Pago no exitoso'),
                    ]);
                    Log::channel('mercadopago')->info("PaymentHistory creado para usuario $userId con status $status");
                } else {
                    Log::channel('mercadopago')->info("PaymentHistory ya existe (evitando duplicado) para usuario $userId con status $status");
                }

                $user = User::find($userId);

                // Enviar email según el estado del pago
                if ($status == 'approved' || $status == 'authorized') {
                    // Pago exitoso - Marcar como NO deudor
                    if ($user) {
                        $user->update(['is_debtor' => false]);
                        $this->sendEmailSafely($user->email, new NewPayment($user), 'Pago exitoso - usuario');
                    } else {
                        Log::channel('mercadopago')->error("Usuario no encontrado al procesar pago exitoso: $userId");
                    }
                } else {
                    // Pago fallido - Marcar como deudor
                    if ($user) {
                        $user->update(['is_debtor' => true]);
                        $this->sendEmailSafely($user->email, new FailedPayment($user), 'Pago fallido - usuario');
                        $this->sendEmailSafely(config('services.research_on_demand.email'), new NotificationFailedPayment($user), 'Pago fallido - admin');
                    } else {
                        Log::channel('mercadopago')->error("Usuario no encontrado al procesar pago fallido: $userId");
                    }
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

    public function subscription_plan_by_id(Request $request, $id)
    {
        $userId = Auth::id();
        $action = "subscription_plan_by_id";

        try {
            $perPage = $request->query('per_page');

            // Query base: historial de pagos
            $query = PaymentHistory::where('id_user', $id)
                ->orderBy('created_at', 'desc');

            // Paginado o listado completo
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

            // Transformación de datos
            $collection->transform(function ($payment) {
                // Convertir 'data' a array si viene como string
                $payment->data = is_string($payment->data) ? json_decode($payment->data, true) : $payment->data;

                // Último UserPlan asociado al preapproval_id
                $latestUserPlan = UserPlan::where('preapproval_id', $payment->preapproval_id)
                    ->orderBy('id', 'desc')
                    ->first();

                $payment->user_plan = $latestUserPlan
                    ? ['id' => $latestUserPlan->id]
                    : null;

                return $payment;
            });

            $data = $collection;

            // Auditoría exitosa
            Audith::new($userId, $action, $id, 200, compact("action", "data", "metaData"));

            return response()->json([
                "message" => $action,
                "data" => $data,
                "meta" => $metaData
            ], 200);

        } catch (Exception $e) {
            $response = [
                "message" => "Error al obtener registros",
                "error" => $e->getMessage(),
                "line" => $e->getLine(),
            ];

            // Auditoría con error
            Audith::new($userId, $action, $id, 500, $response);

            Log::error($response);

            return response()->json($response, 500);
        }
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
        $oneWeekAgo = Carbon::today()->subDays(7); // 7 días atrás para recuperar casos no procesados
        $tomorrow = Carbon::tomorrow();

        $accessToken = config('app.mercadopago_token');

        // Buscar suscripciones que:
        // 1. Tengan fecha de pago en la última semana hasta mañana (recuperar todos los días que fallaron)
        // 2. Fechas más viejas SOLO si es deudor (evitar duplicados)
        $userPlans = UserPlan::where('id_plan', 2)
            ->where(function($query) use ($today, $tomorrow, $oneWeekAgo) {
                // Caso 1: Fechas desde hace 7 días hasta mañana - procesar siempre
                $query->where(function($q) use ($oneWeekAgo, $tomorrow) {
                    $q->whereDate('next_payment_date', '>=', $oneWeekAgo)
                      ->whereDate('next_payment_date', '<=', $tomorrow);
                })
                // Caso 2: Fechas más antiguas (más de 7 días atrás) SOLO si es deudor
                ->orWhere(function($q) use ($oneWeekAgo) {
                    $q->whereDate('next_payment_date', '<', $oneWeekAgo)
                      ->whereHas('user', function($userQuery) {
                          $userQuery->where('is_debtor', true);
                      });
                });
            })
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($plan) use ($priceMonthly, $priceYearly, $accessToken) {
                $data = json_decode($plan->data, true);
                $frequency = $data['auto_recurring']['frequency'] ?? null;
                $status = $data['status'] ?? null;
                $preapprovalId = $plan->preapproval_id;
                $userId = $plan->id_user;

                if ($frequency && $preapprovalId && $status != "cancelled" && $status) {
                    $newAmount = $frequency == 1 ? $priceMonthly : $priceYearly;

                    $payload = [
                        'auto_recurring' => [
                            'transaction_amount' => $newAmount
                        ]
                    ];

                    // Si la suscripción está pausada, intentar reactivarla
                    if ($status == "paused") {
                        Log::channel('mercadopago')->info("Intentando reactivar suscripción pausada: $preapprovalId para usuario: $userId");

                        // Intentar reactivar la suscripción
                        $payload['status'] = 'authorized';
                    }

                    // PUT request to MercadoPago
                    $response = Http::withToken($accessToken)
                        ->put("https://api.mercadopago.com/preapproval/{$preapprovalId}", $payload);

                    if ($response->successful()) {
                        // Guardamos la nueva data devuelta por MercadoPago
                        $newData = $response->json();
                        $plan->data = json_encode($newData);
                        $plan->save();

                        // Si era pausada y se reactivó exitosamente
                        if ($status == "paused" && $newData['status'] == 'authorized') {
                            Log::channel('mercadopago')->info("Suscripción reactivada exitosamente para usuario: $userId");

                            // Marcar como NO deudor porque la suscripción se reactivó
                            $user = User::find($userId);
                            if ($user) {
                                $user->update(['is_debtor' => false]);
                            }

                            $data['reactivation_success'] = true;
                        }
                    } else {
                        // Si falló la reactivación de una suscripción pausada
                        if ($status == "paused") {
                            $user = User::find($userId);

                            if ($user) {
                                // Verificar si ya usó su período de gracia
                                if ($user->grace_period_used) {
                                    // Ya usó el período de gracia, bajar a plan gratuito
                                    Log::channel('mercadopago')->error("No se pudo reactivar suscripción pausada: $preapprovalId. Usuario ya usó período de gracia. Cambiando a plan gratuito.");

                                    $user->update([
                                        'id_plan' => 1,
                                        'is_debtor' => false  // Ya no es deudor porque se le cambió a plan gratuito
                                        // Mantener grace_period_used = true para evitar que vuelva a tener período de gracia
                                    ]);

                                    // Guardar en historial
                                    UserPlan::save_history($userId, 1, ['reason' => 'Suscripción vencida después de período de gracia', 'is_system' => 'true'], now(), $preapprovalId);

                                    // Enviar emails de notificación de forma segura
                                    $this->sendEmailSafely($user->email, new ExpiredSubscription($user), 'Suscripción vencida - usuario');
                                    $this->sendEmailSafely(config('services.research_on_demand.email'), new NotificationExpiredSubscription($user), 'Suscripción vencida - admin');

                                    $data['downgraded_to_free'] = true;
                                    $data['reason'] = 'grace_period_expired';
                                } else {
                                    // Primera vez que falla, otorgar período de gracia (mantener plan)
                                    Log::channel('mercadopago')->warning("No se pudo reactivar suscripción pausada: $preapprovalId. Otorgando período de gracia al usuario $userId.");

                                    $user->update([
                                        'grace_period_used' => true,
                                        'is_debtor' => true
                                    ]);

                                    $data['grace_period_granted'] = true;
                                    $data['reason'] = 'grace_period_granted';
                                }
                            } else {
                                Log::channel('mercadopago')->error("Usuario no encontrado al procesar vencimiento: $userId");
                            }
                        }

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
