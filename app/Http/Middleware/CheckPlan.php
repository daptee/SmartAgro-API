<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckPlan
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Verificar que el usuario esté autenticado y tenga id_plan >= 2
        if (!$user || $user->id_plan < 2) {
            return response()->json(['message' => 'Usted no tiene permisos.'], 403);
        }

        return $next($request);
    }
}
