<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Exception;
use Log;

class ReferredController extends Controller
{
    /**
     * Listar todos los referidos de un usuario (influencer)
     */
    public function index($id)
    {
        try {
            $influencer = User::findOrFail($id);

            $data = $influencer->referredUsers()->get();

            return response(compact('data'));
        } catch (Exception $e) {
            return response(['message' => 'Error al obtener los referidos', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Asignar/generar código de referido a un usuario
     */
    public function store(Request $request, $id)
    {
        $message = "Error al generar código de referido";
        try {
            $user = User::findOrFail($id);

            $request->validate([
                'referral_code' => 'nullable|string|unique:users,referral_code|max:20'
            ]);

            // Si el usuario ya tiene un código de referido, no lo modificamos
            if ($user->referral_code) {
                return response(['message' => 'El usuario ya tiene un código de referido'], 400);
            }

            // Si mandan un código lo usamos, si no generamos uno aleatorio
            $user->referral_code = $request->referral_code ?? strtoupper(Str::random(8));
            $user->save();

            return response(['data' => $user], 201);
        } catch (Exception $e) {
            return response(['message' => $message, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Editar el código de referido de un usuario
     */
    public function update(Request $request, $id)
    {
        $message = "Error al actualizar código de referido";
        try {
            $user = User::findOrFail($id);

            $request->validate([
                'referral_code' => [
                    'required',
                    'string',
                    'max:20',
                    Rule::unique('users')->ignore($user->id)
                ]
            ]);

            $user->referral_code = $request->referral_code;
            $user->save();

            return response(['data' => $user]);
        } catch (Exception $e) {
            return response(['message' => $message, 'error' => $e->getMessage()], 500);
        }
    }

    public function addReferralCode(Request $request, $id)
    {
        $message = "Error al asociar código de referido";
        try {
            $user = User::findOrFail($id);

            $request->validate([
                'referral_code' => 'required|string|exists:users,referral_code',
            ]);

            // Buscar el influencer dueño del código
            $influencer = User::where('referral_code', $request->referral_code)->first();

            // Evitar autorreferencia
            if ($influencer->id === $user->id) {
                return response(['message' => 'Un usuario no puede referirse a sí mismo'], 400);
            }

            Log::info($user);

            // verificar si el influencer esta activo
            if ($influencer->id_status == 2) {
                return response(['message' => 'El usuario referido no está activo'], 400);
            }  

            // Verificar si ya está asociado
            if ($user->referred_by) {
                return response(['message' => 'El usuario ya tiene un código de referido asociado'], 400);
            }

            // Guardar relación
            $user->referred_by = $influencer->id;
            $user->save();

            return response(['message' => 'Código de referido asociado correctamente', 'data' => $user]);
        } catch (Exception $e) {
            return response(['message' => $message, 'error' => $e->getMessage()], 500);
        }
    }
}
