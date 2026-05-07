<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UpdateLastActivity
{
    /**
     * Actualiza el campo last_activity_at del usuario autenticado en cada request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        try {
            $user = Auth::user();
            if ($user) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['last_activity_at' => now()]);
            }
        } catch (\Exception $e) {
            // No interrumpir el flujo si falla el registro de actividad
        }

        return $response;
    }
}
