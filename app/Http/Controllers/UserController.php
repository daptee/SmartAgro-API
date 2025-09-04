<?php

namespace App\Http\Controllers;

use App\Models\Audith;
use App\Models\CompanyInvitation;
use App\Models\User;
use App\Models\UserPlan;
use App\Models\UserProfile;
use App\Models\UsersCompany;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class UserController extends Controller
{

    public $model = User::class;
    public $s = "usuario";
    public $sp = "usuarios";
    public $ss = "usuario/s";
    public $v = "o";
    public $pr = "el";
    public $prp = "los";

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $action = "Listado de usuarios";
        $id_user = Auth::user()->id ?? null;

        try {
            // Parámetros de búsqueda y filtros
            $search = $request->input('search');
            $planId = $request->input('id_plan');
            $statusId = $request->input('id_status');
            $perPage = $request->input('per_page');

            // Query base
            $query = User::with(['status', 'plan'])
                ->orderBy('id', 'desc');

            // Buscador
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });
            }

            // Filtros
            if (!empty($planId)) {
                $query->where('id_plan', $planId);
            }
            if (!empty($statusId)) {
                $query->where('id_status', $statusId);
            }

            // Paginado o listado completo
            if ($perPage) {
                $paginator = $query->paginate((int) $perPage);
                $data = $paginator->items();
                $meta = [
                    "total" => $paginator->total(),
                    "per_page" => $paginator->perPage(),
                    "current_page" => $paginator->currentPage(),
                    "last_page" => $paginator->lastPage(),
                ];
            } else {
                $data = $query->get();
                $meta = null;
            }

            // Auditoría
            Audith::new($id_user, $action, null, 200, compact("action", "data", "meta"));

            $message = $action;
            return response()->json(compact("message", "data", "meta"), 200);

        } catch (Exception $e) {
            $response = [
                "message" => "Error al obtener registros",
                "error" => $e->getMessage(),
                "line" => $e->getLine()
            ];
            Audith::new($id_user, $action, null, 500, $response);
            Log::debug($response);
            return response()->json($response, 500);
        }
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $action = "Detalle de usuario";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $data = $this->model::getAllDataUser($id);

            if ($data['id_plan'] == 3) {
                $company = UsersCompany::where('id_user', $data['id'])
                    ->with([
                        'rol',
                        'plan.company.locality',
                        'plan.company.status',
                        'plan.company.category',
                        'plan' => function ($query) {
                            $query->where('status_id', 1);
                        }
                    ])
                    ->first();

                $data['rol'] = $company?->rol;
                $data['company_plan'] = $company?->plan;

                // 🔍 Buscar la invitación asociada (por email del usuario)
                $invitation = CompanyInvitation::where('mail', $data['email'])
                    ->where('id_company_plan', $company?->id_company_plan)
                    ->latest()
                    ->first();

                if ($invitation && $invitation->invited_by) {
                    $invitedByUser = User::find($invitation->invited_by);
                    if ($invitedByUser) {
                        $data['invited_by_user'] = [
                            'id' => $invitedByUser->id,
                            'name' => $invitedByUser->name,
                            'last_name' => $invitedByUser->last_name,
                            'email' => $invitedByUser->email,
                        ];
                    }
                } else {
                    $data['invited_by_user'] = null; // No se encontró invitación o no hay usuario asociado
                }
            }

            Audith::new($id_user, $action, ["user_id" => $id], 200, compact("data"));
        } catch (Exception $e) {
            $response = ["message" => $action, "error" => $e->getMessage(), "line" => $e->getLine()];
            Log::debug($response);
            Audith::new($id_user, $action, ["user_id" => $id], 500, $response);
            return response($response, 500);
        }

        return response(compact("data"));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    public function get_user_profile(Request $request)
    {
        $message = "Error al obtener registro";
        $action = "Perfil de usuario";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $data = User::getAllDataUser($id_user);

            if ($data['id_plan'] != 1) {
                Log::info($data['id']);
                $existingRecord = UserPlan::where('id_user', $data['id'])->latest()->first();
                if ($existingRecord) {
                    $existingRecord->data = json_decode($existingRecord->data, true);
                }

                $data['plan']['user_plan'] = $existingRecord ?? 'Sin plan asignado';
                Log::info($data);
            }

            if ($data['id_plan'] == 3) {
                $company = UsersCompany::where('id_user', $data['id'])
                    ->with([
                        'rol',
                        'plan.company.locality',
                        'plan.company.status',
                        'plan.company.category',
                        'plan' => function ($query) {
                            $query->where('status_id', 1);
                        }
                    ])
                    ->first();

                $data['rol'] = $company?->rol;
                $data['company_plan'] = $company?->plan;

                // 🔍 Buscar la invitación asociada (por email del usuario)
                $invitation = CompanyInvitation::where('mail', $data['email'])
                    ->where('id_company_plan', $company?->id_company_plan)
                    ->latest()
                    ->first();

                if ($invitation && $invitation->invited_by) {
                    $invitedByUser = User::find($invitation->invited_by);
                    if ($invitedByUser) {
                        $data['invited_by_user'] = [
                            'id' => $invitedByUser->id,
                            'name' => $invitedByUser->name,
                            'last_name' => $invitedByUser->last_name,
                            'email' => $invitedByUser->email,
                        ];
                    }
                } else {
                    $data['invited_by_user'] = null; // No se encontró invitación o no hay usuario asociado
                }
            }

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            $response = ["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()];
            Log::debug($response);
            Audith::new($id_user, $action, $request->all(), 500, $response);
            return response($response, 500);
        }

        return response(compact("data"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $id = Auth::user()->id;

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($id),
            ],
        ]);

        $action = "Actualización de usuario";
        $status = 422;

        if ($validator->fails()) {
            $response = [
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ];
            Audith::new($id, $action, $request->all(), $status, $response);
            return response()->json($response, $status);
        }

        $message = "Usuario actualizado con exito";
        try {
            DB::beginTransaction();
            $user = User::find($id);
            $user->update($request->all());

            $data = User::getAllDataUser($id);
            Audith::new($id, $action, $request->all(), 200, compact("message", "data"));
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $response = ["message" => "Error al actualizar usuario", "error" => $e->getMessage(), "line" => $e->getLine()];
            Audith::new($id, $action, $request->all(), 500, $response);
            Log::debug($response);
            return response($response, 500);
        }

        return response(compact("message", "data"));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy()
    {
        $action = "Eliminación de usuario";
        $id_user = Auth::user()->id;
        $message = "Usuario eliminado con éxito";

        try {
            DB::beginTransaction();

            $user = User::find($id_user);
            if (!$user) {
                $response = ['message' => 'Usuario no encontrado'];
                Audith::new($id_user, $action, ['deleted_user_id' => $id_user], 500, $response);
                return response()->json($response, 404);
            }

            $user->delete();

            Audith::new($id_user, $action, ['deleted_user_id' => $id_user], 200, compact("message"));

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $response = ["message" => "Error al eliminar el usuario", "error" => $e->getMessage(), "line" => $e->getLine()];
            Audith::new($id_user, $action, ['deleted_user_id' => $id_user], 500, $response);
            Log::debug($response);
            return response()->json($response, 500);
        }

        return response()->json(compact("message"));
    }

    public function users_profiles()
    {
        $action = "Listado de perfiles de usuario";
        $data = null;
        $id_user = Auth::user()->id ?? null;
        try {
            $data = UserProfile::orderBy('name')->get();
            Audith::new($id_user, $action, null, 200, compact("action", "data"));
        } catch (Exception $e) {
            $response = ["message" => "Error al obtener registros", "error" => $e->getMessage(), "line" => $e->getLine()];
            Audith::new($id_user, $action, null, 500, $response);
            Log::debug($response);
            return response()->json($response, 500);
        }

        $message = $action;
        return response(compact("message", "data"));
    }

    public function change_status(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'id_status' => 'required|numeric|exists:users_status,id'
        ]);

        $action = "Actualización de estado de usuario";
        $status = 422;
        $id_user = Auth::user()->id ?? null;

        if ($validator->fails()) {
            $response = [
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ];
            Audith::new($id_user, $action, $request->all(), $status, $response);
            return response()->json($response, $status);
        }

        $message = "Actualización de estado de usuario exitosa.";
        $data = null;
        try {
            DB::beginTransaction();

            $user = $this->model::find($id);

            if (!$user) {
                $response = ['message' => 'Usuario no valido.'];
                Audith::new($id_user, $action, ["user_id" => $id, "data" => $request->all()], 400, $response);
                return response()->json($response, 400);
            }

            $user->id_status = $request->id_status;
            $user->save();

            $data = $this->model::getAllDataUser($id);
            Audith::new($id_user, $action, ["user_id" => $id, "data" => $request->all()], 200, compact("message", "data"));
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $response = ["message" => "Error al actualizar estado de usuario", "error" => $e->getMessage(), "line" => $e->getLine()];
            Log::debug($response);
            Audith::new($id_user, $action, ["user_id" => $id, "data" => $request->all()], 500, $response);
            return response($response, 500);
        }

        return response(compact("message", "data"));
    }

    public function change_plan(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'id_plan' => 'required|numeric|exists:plans,id'
        ]);

        $action = "Actualización de plan de usuario";
        $status = 422;
        $id_user = Auth::user()->id ?? null;

        if ($validator->fails()) {
            $response = [
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ];
            Audith::new($id_user, $action, ["id_user" => $id, "data" => $request->all()], $status, $response);
            return response()->json($response, $status);
        }

        $message = "Actualización de plan de usuario exitosa.";
        $data = null;
        try {
            DB::beginTransaction();
            $user = $this->model::find($id);

            if (!$user) {
                $response = ["message" => 'Usuario no valido.'];
                Audith::new($id_user, $action, ["id_user" => $id, "data" => $request->all()], 400, $response);
                return response()->json($response, 400);
            }

            $user->id_plan = $request->id_plan;
            $user->save();

            UserPlan::save_history($user->id, $request->id_plan, null, null);

            $data = $this->model::getAllDataUser($id);
            Audith::new($id_user, $action, ["id_user" => $id, "data" => $request->all()], 200, compact("message", "data"));
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $response = ["message" => "Error al actualizar plan de usuario", "error" => $e->getMessage(), "line" => $e->getLine()];
            Log::debug($response);
            Audith::new($id_user, $action, ["id_user" => $id, "data" => $request->all()], 500, $response);
            return response($response, 500);
        }

        return response(compact("message", "data"));
    }

    public function profile_picture(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        $action = "Actualización de imagen de perfil";
        $status = 422;
        $id_user = Auth::user()->id ?? null;

        if ($validator->fails()) {
            $response = [
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ];
            Audith::new($id_user, $action, $request->all(), $status, $response);
            return response()->json($response, $status);
        }

        $message = "Actualización de imagen de perfil exitosa.";
        $data = null;
        try {
            DB::beginTransaction();
            if ($request->id_user) {
                $user = $this->model::find($request->id_user);
                if (!$user) {
                    $response = ["message" => "Usuario invalido"];
                    Audith::new($id_user, $action, $request->all(), $status, $response);
                    return response($response, 400);
                }
            } else {
                $user = Auth::user();
            }

            if ($user->profile_picture) {
                $file_path = public_path($user->profile_picture);

                if (file_exists($file_path))
                    unlink($file_path);
            }

            $path = $this->save_image_public_folder($request->profile_picture, "users/profiles/", null);

            $user->profile_picture = $path;
            $user->save();

            $data = $this->model::getAllDataUser($user->id);
            Audith::new($id_user, $action, $request->all(), 200, compact("message", "data"));
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $response = ["message" => "Error al actualizar imagen de perfil", "error" => $e->getMessage(), "line" => $e->getLine()];
            Log::debug($response);
            Audith::new($id_user, $action, $request->all(), 500, $response);
            return response($response, 500);
        }

        return response(compact("message", "data"));
    }

    public function save_image_public_folder($file, $path_to_save, $variable_id)
    {
        $fileName = Str::random(5) . time() . '.' . $file->extension();

        if ($variable_id) {
            $file->move(public_path($path_to_save . $variable_id), $fileName);
            $path = "/" . $path_to_save . $variable_id . "/$fileName";
        } else {
            $file->move(public_path($path_to_save), $fileName);
            $path = "/" . $path_to_save . $fileName;
        }

        return $path;
    }
}
