<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckModule
{
    /**
     * Verifica que el usuario autenticado tenga permiso sobre el módulo solicitado.
     *
     * Uso en rutas: ->middleware('check_module:nombre_modulo')
     *
     * El rol 'admin' (superadmin) tiene bypass total.
     * Cualquier otro rol debe tener el módulo asignado en role_modules.
     */
    public function handle(Request $request, Closure $next, string $moduleSlug): Response
    {
        $user = Auth::user();

        // Superadmin: acceso irrestricto
        if ($user->roles->contains('name', 'admin')) {
            return $next($request);
        }

        // Verificar que alguno de los roles del usuario tenga el módulo habilitado
        $hasAccess = $user->roles()
            ->whereHas('modules', function ($query) use ($moduleSlug) {
                $query->where('slug', $moduleSlug);
            })
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'message' => 'No tienes permiso para acceder a este módulo.'
            ], 403);
        }

        return $next($request);
    }
}
