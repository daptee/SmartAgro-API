<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use App\Models\UserPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class PlanController extends Controller
{
    public function subscription(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|integer|exists:plans,id',
            'plan_type' => 'required|string|in:monthly,yearly',
            'payer_email' => [
                'required',
                'email',
                function ($attribute, $value, $fail) {
                    if (!User::where('email', $value)->exists()) {
                        $fail('El correo electrónico no está asociado a ningún usuario.');
                    }
                },
            ],
            'card_token_id' => 'required|string',
            'back_url' => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
             // Verificar si el plan corresponde al plan free
            $plan = Plan::find($request->plan_id);

            if ($request->plan_id == 1 || $plan->name == 'Plan Semilla (Free)') {
                return response()->json([
                    'message' => 'El plan no requiere proceso de pago.',
                ], 400);
            }

            // Verificar que el tipo de plan sea válido
            if (!isset($plan[$request->plan_type . "_mp_subscription_id"])) {
                return response()->json([
                    'message' => "El tipo de plan '{$request->plan_type}' no es válido para el plan seleccionado.",
                ], 400);
            }

            $preapproval_plan_id = $plan[$request->plan_type . "_mp_subscription_id"];

            // Realizar la petición POST a la API de Mercado Pago
            $response = Http::withToken(config('services.mercado_pago_access_token'))
                ->post('https://api.mercadopago.com/preapproval', [
                    'preapproval_plan_id' => $preapproval_plan_id,
                    'payer_email' => $request->payer_email,
                    'card_token_id' => $request->card_token_id,
                    'back_url' => config('services.app_url_front') . $request->back_url,
                    'status' => 'authorized',
                ]);

            // Manejar la respuesta
            if ($response->successful()) {

                $user = User::where('email', $request->payer_email)->first();
                $start_date = now();
                $finish_date = null;

                // Calcular la fecha de finalización
                if ($request->plan_type === 'monthly') {
                    $finish_date = $start_date->copy()->addMonth();
                } elseif ($request->plan_type === 'yearly') {
                    $finish_date = $start_date->copy()->addYear();
                }

                // Actualizar el plan y guardar el historial
                $user->id_plan = $request->plan_id;
                $user->save();

                UserPlan::save_history($user->id, $request->plan_id, $start_date->format('Y-m-d'), $finish_date->format('Y-m-d'), $request->price);

                return response()->json([
                    'message' => 'Suscripción creada exitosamente.',
                    'data' => $response->json(),
                ], 201);
            } else {
                return response()->json([
                    'message' => 'Error al crear la suscripción.',
                    'error' => $response->json(),
                ], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al procesar la solicitud.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
