<?php

namespace App\Http\Controllers;

use App\Models\AdminModule;
use App\Models\Audith;
use App\Models\Role;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminRoleController extends Controller
{
    /**
     * GET /admin/roles
     * Lista todos los roles de administración con sus módulos asignados.
     */
    public function index()
    {
        $action  = "Listado de roles de administración";
        $id_user = Auth::user()->id ?? null;

        try {
            $data = Role::where('is_admin_role', 1)->with('modules')->get();
            Audith::new($id_user, $action, null, 200, compact('data'));
            return response()->json(compact('data'), 200);
        } catch (Exception $e) {
            $response = ['message' => 'Error al obtener roles', 'error' => $e->getMessage(), 'line' => $e->getLine()];
            Audith::new($id_user, $action, null, 500, $response);
            Log::debug($response);
            return response()->json($response, 500);
        }
    }

    /**
     * GET /admin/roles/{id}
     * Detalle de un rol con sus módulos asignados.
     */
    public function show(string $id)
    {
        $action  = "Detalle de rol de administración";
        $id_user = Auth::user()->id ?? null;

        try {
            $data = Role::with('modules')->find($id);

            if (!$data) {
                return response()->json(['message' => 'Rol no encontrado'], 404);
            }

            Audith::new($id_user, $action, ['id' => $id], 200, compact('data'));
            return response()->json(compact('data'), 200);
        } catch (Exception $e) {
            $response = ['message' => 'Error al obtener rol', 'error' => $e->getMessage(), 'line' => $e->getLine()];
            Audith::new($id_user, $action, ['id' => $id], 500, $response);
            Log::debug($response);
            return response()->json($response, 500);
        }
    }

    /**
     * GET /admin/modules
     * Lista todos los módulos disponibles (para usar en formularios de creación/edición de roles).
     */
    public function modules()
    {
        $action  = "Listado de módulos de administración";
        $id_user = Auth::user()->id ?? null;

        try {
            $data = AdminModule::orderBy('id')->get();
            Audith::new($id_user, $action, null, 200, compact('data'));
            return response()->json(compact('data'), 200);
        } catch (Exception $e) {
            $response = ['message' => 'Error al obtener módulos', 'error' => $e->getMessage(), 'line' => $e->getLine()];
            Audith::new($id_user, $action, null, 500, $response);
            Log::debug($response);
            return response()->json($response, 500);
        }
    }

    /**
     * POST /admin/roles
     * Crea un nuevo rol de administración con módulos asignados.
     *
     * Body: { "name": "editor", "description": "...", "module_ids": [1, 3, 5] }
     */
    public function store(Request $request)
    {
        $action  = "Creación de rol de administración";
        $id_user = Auth::user()->id ?? null;

        $request->validate([
            'name'         => 'required|string|max:50|unique:roles,name',
            'description'  => 'nullable|string|max:255',
            'module_ids'   => 'required|array|min:1',
            'module_ids.*' => 'integer|exists:admin_modules,id',
        ]);

        try {
            DB::beginTransaction();

            $moduleIds = collect($request->module_ids)->sort()->values()->toArray();
            $permissionsHash = hash('sha256', implode(',', $moduleIds));

            $role = Role::create([
                'name'             => $request->name,
                'description'      => $request->description,
                'is_admin_role'    => 1,
                'permissions_hash' => $permissionsHash,
            ]);

            $role->modules()->sync($moduleIds);
            $role->load('modules');

            DB::commit();

            $message = "Rol creado con éxito";
            Audith::new($id_user, $action, $request->all(), 201, compact('message', 'role'));
            return response()->json(compact('message', 'role'), 201);
        } catch (Exception $e) {
            DB::rollBack();
            $response = ['message' => 'Error al crear rol', 'error' => $e->getMessage(), 'line' => $e->getLine()];
            Audith::new($id_user, $action, $request->all(), 500, $response);
            Log::debug($response);
            return response()->json($response, 500);
        }
    }

    /**
     * PUT /admin/roles/{id}
     * Actualiza nombre, descripción y módulos de un rol.
     * El rol 'admin' (superadmin) no puede ser modificado.
     *
     * Body: { "name": "editor", "description": "...", "module_ids": [1, 3, 5] }
     */
    public function update(Request $request, string $id)
    {
        $action  = "Actualización de rol de administración";
        $id_user = Auth::user()->id ?? null;

        $role = Role::find($id);
        if (!$role) {
            return response()->json(['message' => 'Rol no encontrado'], 404);
        }

        if ($role->name === 'admin') {
            return response()->json(['message' => 'El rol admin no puede ser modificado'], 403);
        }

        $request->validate([
            'name'         => 'sometimes|required|string|max:50|unique:roles,name,' . $id,
            'description'  => 'nullable|string|max:255',
            'module_ids'   => 'sometimes|required|array|min:1',
            'module_ids.*' => 'integer|exists:admin_modules,id',
        ]);

        try {
            DB::beginTransaction();

            $updateData = [
                'name'        => $request->input('name', $role->name),
                'description' => $request->input('description', $role->description),
            ];

            if ($request->has('module_ids')) {
                $moduleIds = collect($request->module_ids)->sort()->values()->toArray();
                $role->modules()->sync($moduleIds);
                $updateData['permissions_hash'] = hash('sha256', implode(',', $moduleIds));
            }

            $role->update($updateData);
            $role->load('modules');

            DB::commit();

            $message = "Rol actualizado con éxito";
            Audith::new($id_user, $action, $request->all(), 200, compact('message', 'role'));
            return response()->json(compact('message', 'role'), 200);
        } catch (Exception $e) {
            DB::rollBack();
            $response = ['message' => 'Error al actualizar rol', 'error' => $e->getMessage(), 'line' => $e->getLine()];
            Audith::new($id_user, $action, $request->all(), 500, $response);
            Log::debug($response);
            return response()->json($response, 500);
        }
    }
}
