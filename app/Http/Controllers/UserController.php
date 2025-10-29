<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeUserMailable;
use App\Models\Audith;
use App\Models\CompanyInvitation;
use App\Models\User;
use App\Models\UserPlan;
use App\Models\UserProfile;
use App\Models\UsersCompany;
use App\Models\UserStatus;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
            $profileId = $request->input('id_user_profile');
            $countryId = $request->input('id_country');
            $provinceId = $request->input('id_province');
            $localityId = $request->input('id_locality');
            $userProfileId = $request->input('id_user_profile');
            $statusId = $request->input('id_status');
            $perPage = $request->input('per_page');

            // Query base
            $query = User::with(['status', 'plan', 'locality'])
                ->orderBy('name', 'asc');

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
            if (!empty($profileId)) {
                $query->where('id_user_profile', $profileId);
            }
            if (!empty($countryId)) {
                $query->where('id_country', $countryId);
            }
            if (!empty($localityId)) {
                $query->where('id_locality', $localityId);
            }
            if (!empty($provinceId)) {
                $query->whereHas('locality', function ($q) use ($provinceId) {
                    $q->where('province_id', $provinceId);
                });
            }
            if (!empty($userProfileId)) {
                $query->where('id_user_profile', $userProfileId);
            };

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
    public function create(Request $request)
    {
        $id_user = Auth::user()->id ?? null;
        $action = "Creación de usuario";

        // Validaciones
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'id_locality' => 'nullable|integer|exists:localities,id',
            'id_country' => 'nullable|integer|exists:countries,id',
            'locality_name' => 'nullable|string|max:150',
            'province_name' => 'nullable|string|max:150',
            'id_user_profile' => 'required|integer|exists:users_profiles,id',
            'id_status' => 'required|integer|exists:users_status,id',
            'id_plan' => 'required|integer|exists:plans,id',
            'id_company_plan' => 'nullable|exists:companies,id',
            'id_user_company_rol' => 'nullable|exists:users_company_roles,id',
            'referral_code' => 'nullable|string|unique:users,referral_code',
            'referred_by' => 'nullable|exists:users,id',
            'profile_picture' => 'nullable|string',
            'password' => 'nullable|string|min:8',
        ]);

        if ($validator->fails()) {
            $response = [
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ];
            Audith::new($id_user, $action, $request->all(), 422, $response);
            return response()->json($response, 422);
        }

        try {
            DB::beginTransaction();

            // Si no envían contraseña, generar una por defecto
            $password = $request->input('password')
                ? $request->input('password')
                : Str::random(10); // puedes poner una fija si prefieres

            $user = User::create([
                'name' => $request->input('name'),
                'last_name' => $request->input('last_name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'password' => $password,
                'id_locality' => $request->input('id_locality'),
                'id_country' => $request->input('id_country'),
                'locality_name' => $request->input('locality_name'),
                'province_name' => $request->input('province_name'),
                'id_user_profile' => $request->input('id_user_profile'),
                'id_status' => $request->input('id_status'),
                'profile_picture' => $request->input('profile_picture'),
                'id_plan' => $request->input('id_plan'),
            ]);

            $data = User::getAllDataUser($user->id);

            if ($request->id_company_plan) {
                if ($request->id_user_company_rol != 1) {
                    $data = CompanyInvitation::create([
                        'id_company_plan' => $request->id_company_plan,
                        'mail' => $request->email,
                        'id_user_company_rol' => $request->id_user_company_rol ?? 2,
                        'invitation_date' => Carbon::now(),
                        'invited_by' => $id_user,
                        'status_id' => 2,
                    ]);
                }

                // Crear relación users_companies
                $userCompany = UsersCompany::create([
                    'id_user' => $user->id,
                    'id_company_plan' => $request->id_company_plan,
                    'id_user_company_rol' => $request->id_user_company_rol ?? 2
                ]);
            }
            ;

            if ($request->referral_code) {
                $influencer = User::where('referral_code', $request->referral_code)->first();

                // Evitar autorreferencia
                if ($influencer->id === $user->id) {
                    return response(['message' => 'Un usuario no puede referirse a sí mismo'], 400);
                }

                // Guardar relación
                $user->referred_by = $influencer->id;
                $user->save();
            }

            if ($request->referred_by) {
                $referrer = User::find($request->referred_by);
                // Evitar autorreferencia
                if ($referrer->id === $user->id) {
                    return response(['message' => 'Un usuario no puede ser referido por sí mismo'], 400);
                }

                // Guardar relación
                $user->referred_by = $referrer->id;
                $user->save();
            }

            DB::commit();

            $message = "Usuario creado con éxito";
            Mail::to($user->email)->send(new WelcomeUserMailable($user));
            Audith::new($id_user, $action, $request->all(), 201, compact("message", "data"));
            return response(compact("message", "data"), 201);
        } catch (Exception $e) {
            DB::rollBack();
            $response = ["message" => "Error al crear usuario", "error" => $e->getMessage(), "line" => $e->getLine()];
            Log::debug($response);
            Audith::new($id_user, $action, $request->all(), 500, $response);
            return response($response, 500);
        }
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
    public function edit(Request $request, string $id)
    {
        $id_user = Auth::user()->id ?? null;
        $action = "Edición de usuario";

        // Buscar el usuario
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        // Validaciones
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id), // permite mantener su mismo email
            ],
            'phone' => 'nullable|string|max:20',
            'id_locality' => 'nullable|integer|exists:localities,id',
            'id_country' => 'nullable|integer|exists:countries,id',
            'locality_name' => 'nullable|string|max:150',
            'province_name' => 'nullable|string|max:150',
            'id_user_profile' => 'required|integer|exists:users_profiles,id',
            'id_status' => 'required|integer|exists:users_status,id',
            'id_plan' => 'required|integer|exists:plans,id',
            'id_company_plan' => 'nullable|exists:companies,id',
            'id_user_company_rol' => 'nullable|exists:users_company_roles,id',
            'referral_code' => [
                'nullable',
                'string',
                Rule::unique('users', 'referral_code')->ignore($user->id),
            ],
            'referred_by' => 'nullable|exists:users,id',
            'password' => 'nullable|string|min:8',
        ]);

        if ($validator->fails()) {
            $response = [
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ];
            Audith::new($id_user, $action, $request->all(), 422, $response);
            return response()->json($response, 422);
        }

        try {
            DB::beginTransaction();

            // Actualizar usuario
            $user->update([
                'name' => $request->input('name'),
                'last_name' => $request->input('last_name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'password' => $request->input('password') ?? $user->password,
                'id_locality' => $request->input('id_locality'),
                'id_country' => $request->input('id_country'),
                'locality_name' => $request->input('locality_name'),
                'province_name' => $request->input('province_name'),
                'id_user_profile' => $request->input('id_user_profile'),
                'id_status' => $request->input('id_status'),
                'id_plan' => $request->input('id_plan'),
            ]);

            // Guardamos el company actual (si existe)
            $oldCompanyId = UsersCompany::where('id_user', $user->id)->value('id_company_plan');

            if ($request->id_company_plan) {
                if ($oldCompanyId && $oldCompanyId != $request->id_company_plan) {
                    if ($request->id_user_company_rol != 1) {
                        $data = CompanyInvitation::create([
                            'id_company_plan' => $request->id_company_plan,
                            'mail' => $request->email,
                            'id_user_company_rol' => $request->id_user_company_rol ?? 2,
                            'invitation_date' => Carbon::now(),
                            'invited_by' => $id_user,
                            'status_id' => 2,
                        ]);
                    }
                }

                // Actualizar relación
                UsersCompany::updateOrCreate(
                    [
                        'id_user' => $user->id,
                    ],
                    [
                        'id_company_plan' => $request->id_company_plan,
                        'id_user_company_rol' => $request->id_user_company_rol ?? 2,
                    ]
                );
            }

            // Actualizar referido si corresponde
            if ($request->referral_code) {
                $user->referral_code = $request->referral_code;
                $user->save();
            }

            if ($request->referred_by) {
                $referrer = User::find($request->referred_by);
                if ($referrer && $referrer->id !== $user->id) {
                    $user->referred_by = $referrer->id;
                    $user->save();
                } else {
                    return response(['message' => 'Un usuario no puede ser referido por sí mismo'], 400);
                }
            }

            DB::commit();

            $data = User::getAllDataUser($user->id);
            $message = "Usuario actualizado con éxito";
            Audith::new($id_user, $action, $request->all(), 200, compact("message", "data"));
            return response(compact("message", "data"), 200);
        } catch (Exception $e) {
            DB::rollBack();
            $response = ["message" => "Error al actualizar usuario", "error" => $e->getMessage(), "line" => $e->getLine()];
            Log::debug($response);
            Audith::new($id_user, $action, $request->all(), 500, $response);
            return response($response, 500);
        }
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

    public function destroy_by_id(string $id)
    {
        $action = "Eliminación de usuario";
        $id_user = $id;
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

    public function get_user_status(Request $request)
    {
        $message = "Error al obtener estados de usuarios";
        $action = "Listado de estados de usuarios";
        $data = null;
        $id_user = Auth::user()->id ?? null;
        try {
            $data = UserStatus::all();
            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
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

    public function profile_picture_admin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'id_user' => 'required|integer|exists:users,id',
        ]);

        $action = "Actualización de imagen de perfil desde el administrador";
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
            $user = Auth::user();

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

    public function send_welcome_email(Request $request, $id)
    {
        $action = "Envio de mail de bienvenida";
        $id_user = Auth::user()->id ?? null;

        try {
            $user = User::findOrFail($id);

            Mail::to($user->email)->send(new WelcomeUserMailable($user));

            Audith::new($id_user, $action, ["user_id" => $id], 200, [
                "message" => "Envio de mail exitoso"
            ]);

            return response()->json([
                "message" => "Correo enviado correctamente a {$user->email}"
            ], 200);

        } catch (Exception $e) {
            $response = [
                "message" => "Error al enviar correo",
                "error" => $e->getMessage(),
                "line" => $e->getLine()
            ];

            Audith::new($id_user, $action, ["user_id" => $id], 500, $response);
            Log::error($response);

            return response()->json($response, 500);
        }
    }
}
