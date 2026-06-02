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
     * Acciones disponibles por módulo.
     * GET siempre está permitido si el módulo está asignado, por lo que no aparece aquí.
     * La validación acepta cualquier string; este mapa se expone en GET /admin/actions.
     */
    private const MODULE_ACTIONS = [
        'usuarios' => [
            'store', 'update', 'destroy', 'changeStatus', 'profilePictureAdmin',
        ],
        'planes_empresa' => [
            'store', 'update', 'changeStatus',
        ],
        'gestion_empresas' => [
            'store', 'update', 'updateLogo', 'addMainAdminCompanyPlan',
        ],
        'gestion_publicidades' => [
            'store', 'update', 'changeStatus',
        ],
        'espacios_publicitarios' => [
            'store', 'update', 'changeStatus',
        ],
        'mercado_news' => [
            'store', 'update', 'destroy', 'changeStatus', 'updateImage', 'deleteImage',
        ],
        'mercado_mag_lease_index' => [
            'store', 'update', 'destroy', 'changeStatus',
        ],
        'mercado_mag_steer_index' => [
            'store', 'update', 'destroy', 'changeStatus',
        ],
        'mercado_major_crops' => [
            'store', 'update', 'destroy', 'changeStatus',
        ],
        'mercado_insights' => [
            'store', 'update', 'destroy', 'changeStatus',
        ],
        'mercado_rainfall_records' => [
            'store', 'update', 'destroy', 'changeStatus',
        ],
        'mercado_main_grain_prices' => [
            'store', 'update', 'destroy', 'changeStatus',
        ],
        'mercado_price_active_ingredients' => [
            'store', 'update', 'destroy', 'changeStatus',
        ],
        'mercado_producer_segment_prices' => [
            'store', 'update', 'destroy', 'changeStatus',
        ],
        'mercado_general_control' => [
            'store', 'update', 'destroy', 'changeStatus', 'replicateAdditionalInfo', 'updateData', 'export', 'import',
        ],
        'indicadores_pit' => [
            'store', 'update', 'destroy', 'changeStatus',
        ],
        'indicadores_gross_margins' => [
            'store', 'update', 'destroy', 'changeStatus',
        ],
        'indicadores_gross_margins_trend' => [
            'store', 'update', 'destroy', 'changeStatus', 'deleteDuplicates',
        ],
        'indicadores_livestock' => [
            'store', 'update', 'destroy', 'changeStatus', 'deleteDuplicates',
        ],
        'indicadores_agricultural' => [
            'store', 'update', 'destroy', 'changeStatus', 'deleteDuplicates',
        ],
        'indicadores_product_prices' => [
            'store', 'update', 'destroy', 'changeStatus',
        ],
        'indicadores_harvest_prices' => [
            'store', 'update', 'destroy', 'changeStatus', 'deleteDuplicates',
        ],
        'indicadores_traffic_light' => [
            'store', 'update', 'destroy', 'changeStatus',
        ],
        'indicadores_business_controls' => [
            'store', 'update', 'destroy', 'changeStatus', 'replicateAdditionalInfo', 'updateData', 'export', 'import',
        ],
        'config_iconos' => [
            'store', 'update', 'destroy',
        ],
        'config_imagenes' => [
            'store', 'update', 'destroy',
        ],
        'config_faqs' => [
            'store', 'update', 'destroy', 'changeStatus',
        ],
        'config_regiones' => [
            'store', 'update', 'destroy', 'changeStatus',
        ],
        'config_perfiles' => [
            'store', 'update', 'destroy', 'changeStatus',
        ],
        'config_planes' => [
            'update',
        ],
        'config_clasificaciones' => [
            'store', 'update', 'destroy', 'changeStatus',
        ],
        'config_productos' => [
            'store', 'update', 'destroy', 'changeStatus',
        ],
        'config_cultivos' => [
            'store', 'update', 'destroy',
        ],
        'config_unidades' => [
            'store', 'update', 'destroy', 'changeStatus',
        ],
        'config_variables' => [
            'store', 'update', 'destroy', 'changeStatus',
        ],
    ];

    /**
     * GET /admin/roles
     * Lista todos los roles de administración con sus módulos y acciones asignadas.
     */
    public function index()
    {
        $action  = "Listado de roles de administración";
        $id_user = Auth::user()->id ?? null;
    
        try {
            // Se quitó ->where('is_admin_role', 1) para traer TODOS los roles
            $data = Role::with(['modules' => function ($q) {
                    $q->select('admin_modules.id', 'admin_modules.slug', 'admin_modules.name')
                      ->withPivot('actions');
                }])
                ->get()
                ->each(function ($role) {
                    $role->modules->each(function ($module) {
                        $module->pivot->actions = is_string($module->pivot->actions)
                            ? json_decode($module->pivot->actions, true)
                            : $module->pivot->actions;
                    });
                });
    
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
     * Detalle de un rol con sus módulos y acciones asignadas.
     */
    public function show(string $id)
    {
        $action  = "Detalle de rol de administración";
        $id_user = Auth::user()->id ?? null;

        try {
            $data = Role::with(['modules' => function ($q) {
                    $q->select('admin_modules.id', 'admin_modules.slug', 'admin_modules.name')
                      ->withPivot('actions');
                }])
                ->find($id);

            if (!$data) {
                return response()->json(['message' => 'Rol no encontrado'], 404);
            }

            $data->modules->each(function ($module) {
                $module->pivot->actions = is_string($module->pivot->actions)
                    ? json_decode($module->pivot->actions, true)
                    : $module->pivot->actions;
            });

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
     * Crea un nuevo rol de administración con módulos y acciones asignadas.
     *
     * Body:
     * {
     *   "name": "editor",
     *   "description": "...",
     *   "modules": [
     *     { "id": 6, "actions": ["read", "create"] },
     *     { "id": 7, "actions": ["read"] }
     *   ]
     * }
     */
    public function store(Request $request)
    {
        $action  = "Creación de rol de administración";
        $id_user = Auth::user()->id ?? null;

        $request->validate([
            'name'                => 'required|string|max:50|unique:roles,name',
            'description'         => 'nullable|string|max:255',
            'modules'             => 'required|array|min:1',
            'modules.*.id'        => 'required|integer|exists:admin_modules,id',
            'modules.*.actions'   => 'required|array|min:1',
            'modules.*.actions.*' => 'string|max:60',
        ]);

        try {
            DB::beginTransaction();

            $permissionsHash = $this->calculateHash($request->modules);

            $role = Role::create([
                'name'             => $request->name,
                'description'      => $request->description,
                'is_admin_role'    => 1,
                'permissions_hash' => $permissionsHash,
            ]);

            $this->syncModules($role, $request->modules);
            $role->load(['modules' => fn($q) => $q->withPivot('actions')]);
            $this->decodeActions($role);

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
     * Actualiza nombre, descripción, módulos y acciones de un rol.
     * El rol 'admin' (superadmin) no puede ser modificado.
     *
     * Body igual que store (todos los campos opcionales).
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
            'name'                => 'sometimes|required|string|max:50|unique:roles,name,' . $id,
            'description'         => 'nullable|string|max:255',
            'modules'             => 'sometimes|required|array|min:1',
            'modules.*.id'        => 'required_with:modules|integer|exists:admin_modules,id',
            'modules.*.actions'   => 'required_with:modules|array|min:1',
            'modules.*.actions.*' => 'string|max:60',
        ]);

        try {
            DB::beginTransaction();

            $updateData = [
                'name'        => $request->input('name', $role->name),
                'description' => $request->input('description', $role->description),
            ];

            if ($request->has('modules')) {
                $this->syncModules($role, $request->modules);
                $updateData['permissions_hash'] = $this->calculateHash($request->modules);
            }

            $role->update($updateData);
            $role->load(['modules' => fn($q) => $q->withPivot('actions')]);
            $this->decodeActions($role);

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

    /**
     * GET /admin/actions
     * Devuelve las acciones disponibles agrupadas por slug de módulo.
     */
    public function actions()
    {
        $action  = "Listado de acciones por módulo";
        $id_user = Auth::user()->id ?? null;

        $data = collect(self::MODULE_ACTIONS)
            ->map(fn($actions, $slug) => [
                'slug'    => $slug,
                'actions' => $actions,
            ])
            ->values();

        Audith::new($id_user, $action, null, 200, compact('data'));
        return response()->json(compact('data'), 200);
    }

    // -------------------------------------------------------
    // Helpers privados
    // -------------------------------------------------------

    /**
     * Calcula el hash SHA-256 a partir de los módulos y sus acciones.
     * Formato: "id:action1,action2|id2:action1" (ordenado por id, acciones ordenadas).
     */
    private function calculateHash(array $modules): string
    {
        $parts = collect($modules)
            ->sortBy('id')
            ->map(function ($m) {
                $actions = collect($m['actions'])->sort()->values()->implode(',');
                return $m['id'] . ':' . $actions;
            })
            ->implode('|');

        return hash('sha256', $parts);
    }

    /**
     * Sincroniza la tabla role_modules con los módulos y acciones enviados.
     * Usa sync con datos de pivot (actions como JSON).
     */
    private function syncModules(Role $role, array $modules): void
    {
        $syncData = [];
        foreach ($modules as $m) {
            $actions = collect($m['actions'])->unique()->sort()->values()->toArray();
            $syncData[$m['id']] = ['actions' => json_encode($actions)];
        }
        $role->modules()->sync($syncData);
    }

    /**
     * Decodifica el JSON de actions en el pivot para que la respuesta devuelva arrays, no strings.
     */
    private function decodeActions(Role $role): void
    {
        $role->modules->each(function ($module) {
            $module->pivot->actions = is_string($module->pivot->actions)
                ? json_decode($module->pivot->actions, true)
                : $module->pivot->actions;
        });
    }
}
