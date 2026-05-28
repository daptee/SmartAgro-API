<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;

class Admin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Verificar si el usuario está autenticado
            if (!JWTAuth::parseToken()->authenticate()) {
                return response()->json(['message' => 'Sesion expirada.'], 401);
            }
        } catch (TokenExpiredException $e) {
            return response()->json(['message' => 'Sesion expirada.'], 401);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Sesion expirada.'], 401);
        }

        $user = JWTAuth::parseToken()->authenticate();

        // Permite el acceso a cualquier usuario con un rol de tipo admin (is_admin_role = 1)
        // El rol 'admin' es superadmin; los nuevos roles también tienen is_admin_role = 1
        $hasAdminRole = $user->roles()->where('is_admin_role', 1)->exists();

        if (!$hasAdminRole) {
            return response()->json(['message' => 'No tienes permiso para acceder a este recurso'], 401);
        }

        return $next($request);
    }
}
