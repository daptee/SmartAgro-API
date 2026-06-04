<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CheckModule
{
    /**
     * Verifica que el usuario autenticado tenga permiso sobre el módulo solicitado.
     *
     * Uso en rutas: ->middleware('check_module:nombre_modulo')
     *
     * Reglas:
     *  - GET siempre pasa si el usuario tiene el módulo asignado (sin verificar acciones).
     *  - POST / PUT / PATCH / DELETE verifican que el nombre del método del controller
     *    esté en el array 'actions' del registro role_modules.
     *
     * El rol 'admin' (superadmin) tiene bypass total.
     */
    public function handle(Request $request, Closure $next, string $moduleSlug): Response
    {
        $user = Auth::user();

        // Superadmin: acceso irrestricto a cualquier módulo/acción
        if ($user->roles->contains('is_admin_role', true)) {
            return $next($request);
        }

        // Buscar el registro role_module del usuario para este módulo
        $roleModule = DB::table('role_modules')
            ->join('admin_modules', 'admin_modules.id', '=', 'role_modules.id_module')
            ->join('user_roles', 'user_roles.id_role', '=', 'role_modules.id_role')
            ->where('user_roles.id_user', $user->id)
            ->where('admin_modules.slug', $moduleSlug)
            ->select('role_modules.actions')
            ->first();

        if (!$roleModule) {
            return response()->json([
                'message' => 'No tienes permiso para acceder a este módulo.',
            ], 403);
        }

        // GET siempre pasa si el módulo está asignado
        if (strtoupper($request->method()) === 'GET') {
            return $next($request);
        }

        // Para otros métodos, verificar que la acción (método del controller) esté permitida
        $controllerAction = $this->resolveControllerMethod($request);

        $allowedActions = is_string($roleModule->actions)
            ? json_decode($roleModule->actions, true)
            : $roleModule->actions;

        if (!$controllerAction || !in_array($controllerAction, $allowedActions ?? [])) {
            return response()->json([
                'message'          => 'No tienes permiso para realizar esta acción.',
                'required_action'  => $controllerAction,
                'allowed_actions'  => $allowedActions ?? [],
            ], 403);
        }

        return $next($request);
    }

    /**
     * Extrae el nombre del método del controller que va a ejecutar la request.
     * Ej: "App\Http\Controllers\NewsController@store" → "store"
     */
    private function resolveControllerMethod(Request $request): ?string
    {
        $action = $request->route()?->getActionName();

        if (!$action || $action === 'Closure') {
            return null;
        }

        // Formato: "App\Http\Controllers\FooController@methodName"
        if (str_contains($action, '@')) {
            return explode('@', $action)[1];
        }

        return null;
    }
}
