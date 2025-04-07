<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;

class Token
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

        // Verificar si el usuario es administrador
        /* if ($user->id_user_type !== 1) {            
            return response()->json(['message' => 'Prohibido'], 401);
        } */

        return $next($request);
    }
}
