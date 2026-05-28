<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class CheckPermissionsHash
{
    /**
     * Verifica que los hashes de permisos almacenados en el token JWT
     * coincidan con los hashes actuales en la base de datos.
     *
     * Si algún rol del usuario tuvo sus módulos modificados después del login,
     * el hash diferirá y se retorna 401 con error_code PERMISSIONS_CHANGED
     * para obligar al frontend a que el usuario se vuelva a loguear.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Sesion expirada.'], 401);
        }

        // Obtener el mapa de hashes que se guardó en el token al momento del login
        // Formato: { "id_rol" => "hash", ... }
        $tokenHashes = Auth::payload()->get('roles_permissions_hash');

        // Si el token es anterior a la implementación de esta feature, no tiene el campo.
        // Se permite pasar para no romper sesiones existentes; en el próximo login se incluirá.
        if (empty($tokenHashes) || !is_array($tokenHashes)) {
            return $next($request);
        }

        // Obtener los roles actuales del usuario con sus hashes desde la BD
        $currentRoles = Role::whereIn('id', array_keys($tokenHashes))
            ->pluck('permissions_hash', 'id');

        foreach ($tokenHashes as $roleId => $tokenHash) {
            $currentHash = $currentRoles->get((int) $roleId);

            // Si el rol ya no existe o su hash cambió, los permisos están desactualizados
            if ($currentHash === null || $currentHash !== $tokenHash) {
                return response()->json([
                    'message'    => 'Tus permisos han cambiado. Por favor, volvé a iniciar sesión.',
                    'error_code' => 'PERMISSIONS_CHANGED',
                ], 401);
            }
        }

        return $next($request);
    }
}
