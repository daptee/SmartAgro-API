<?php

namespace App\Http\Controllers;

use App\Mail\InvitationUserCompanyMailable;
use App\Models\StatusInvitation;
use App\Models\UsersCompany;
use App\Models\CompanyInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Models\Audith;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Mail;

class UserCompanyController extends Controller
{
    public function index(Request $request)
    {
        $message = "Error al obtener los registros de usuarios de empresas";
        $action = "Listado de usuarios de empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $perPage = $request->query('per_page', 10);
            $page = $request->query('page', 1);

            $query = UsersCompany::with(['user', 'company', 'rol']);

            if ($request->filled('user')) {
                $query->where('id_user', $request->user);
            }

            if ($request->filled('company')) {
                $query->where('id_company', $request->company);
            }

            if ($request->filled('rol')) {
                $query->where('id_user_company_rol', $request->rol);
            }

            $results = $query->paginate($perPage, ['*'], 'page', $page);

            $data = [
                'result' => $results->items(),
                'meta_data' => [
                    'page' => $results->currentPage(),
                    'per_page' => $results->perPage(),
                    'total' => $results->total(),
                    'last_page' => $results->lastPage(),
                ]
            ];

            Audith::new($id_user, $action, $request->all(), 200, compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact('data'));
    }

    public function show($id, Request $request)
    {
        $message = "Error al obtener la invitacion";
        $action = "Obtener invitacion a empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;
        try {
            $data = CompanyInvitation::find($id);
            $data->load('rol', 'plan.company.locality', 'plan.company.status', 'plan.company.category', 'plan.status', 'status');
            Audith::new($id_user, $action, $request->all(), 200, compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact('data'));
    }

    public function send_invitation(Request $request)
    {
        $message = "Error al enviar la invitación";
        $action = "Enviar invitación a empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $request->validate([
                'mail' => 'required|email',
                'id_company_plan' => 'required|exists:companies,id',
                'id_user_company_rol' => 'required|exists:users_company_roles,id'
            ]);

            $user = User::where('email', $request->mail)->first();

            if ($user) {
                $response = [
                    'message' => 'Ya hay un usuario registrado con este correo.',
                    'error_code' => 404
                ];
                Audith::new($id_user, $action, $request->all(), 422, $response);
                return response()->json($response, 422);
            }

            $data = CompanyInvitation::create([
                'id_company_plan' => $request->id_company_plan,
                'mail' => $request->mail,
                'id_user_company_rol' => $request->id_user_company_rol,
                'invitation_date' => Carbon::now(),
                'status_id' => 1,
            ]);

            $data->load('rol', 'plan.company.locality', 'plan.company.status', 'plan.company.category', 'plan.status', 'status');
            $plan = $data['plan'];
            $new_user = [
                "email" => $request->mail,
            ];
            Mail::to($request->mail)->send(new InvitationUserCompanyMailable($new_user, $plan, $data));

            Audith::new($id_user, $action, $request->all(), 201, compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact('data'), 201);
    }

    public function resend_invitation(Request $request)
    {
        $message = "Error al reenviar la invitación";
        $action = "Reenviar invitación a empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $request->validate([
                'mail' => 'required|email',
                'id_company_plan' => 'required|exists:companies,id',
            ]);

            $user = User::where('email', $request->mail)->first();

            if ($user) {
                $response = [
                    'message' => 'Ya hay un usuario registrado con este correo.',
                    'error_code' => 404
                ];
                Audith::new($id_user, $action, $request->all(), 422, $response);
                return response()->json($response, 422);
            }

            $invitation = CompanyInvitation::where('mail', $request->mail)
                ->where('id_company_plan', $request->id_company_plan)
                ->latest()
                ->first();

            if (!$invitation) {
                $response = [
                    'message' => 'No se encontró una invitación existente para este correo y empresa.',
                    'error_code' => 404
                ];
                Audith::new($id_user, $action, $request->all(), 404, $response);
                return response()->json($response, 404);
            }

            $invitation->update([
                'invitation_date' => Carbon::now(),
            ]);

            $invitation->load('rol', 'plan.company.locality', 'plan.company.status', 'plan.company.category', 'plan.status', 'status');
            $plan = $invitation['plan'];
            $new_user = [
                "email" => $request->mail,
            ];

            Mail::to($request->mail)->send(new InvitationUserCompanyMailable($new_user, $plan, $invitation));

            $data = $invitation;

            Audith::new($id_user, $action, $request->all(), 200, compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact('data'), 200);
    }

    public function list_invitations(Request $request)
    {
        $message = "Error al obtener las invitaciones";
        $action = "Listado de invitaciones de empresas";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $companyPlan = $request->query('company_plan');
            $status = $request->query('status');
            $perPage = $request->query('per_page', 10);
            $page = $request->query('page', 1);

            $query = CompanyInvitation::with('rol', 'plan.company.locality', 'plan.company.status', 'plan.company.category', 'plan.status', 'status');

            // Aplicar filtro si llega el parámetro 'company'
            if (!is_null($companyPlan)) {
                $query->where('id_company_plan', $companyPlan);
            }

            // Aplicar filtro si llega el parámetro 'status'
            if (!is_null($status)) {
                $query->where('status_id', $status);
            }

            $query->orderBy('created_at', 'desc');

            $results = $query->paginate($perPage, ['*'], 'page', $page);

            // Contar invitaciones activas (status_id 1 o 2)
            $activeInvitationsCount = CompanyInvitation::whereIn('status_id', [1, 2])->count();

            // Procesar cada invitación para verificar si hay un usuario registrado
            $formatted = [];
            foreach ($results->items() as $invitation) {
                $invitationData = $invitation->toArray();

                // Buscar usuario registrado con ese email
                $user = User::where('email', $invitation->mail)->first();
                if ($user) {
                    $invitationData['registered_user'] = [
                        'id_user' => $user->id,
                        'name' => $user->name,
                        'last_name' => $user->last_name,
                        'registered_at' => $user->created_at->toDateTimeString(),
                    ];
                }

                $formatted[] = $invitationData;
            }

            $data = [
                'result' => $formatted,
                'meta_data' => [
                    'page' => $results->currentPage(),
                    'per_page' => $results->perPage(),
                    'total' => $results->total(),
                    'last_page' => $results->lastPage(),
                    'active_invitations_count' => $activeInvitationsCount,
                ]
            ];

            Audith::new($id_user, $action, $request->all(), 200, compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact('data'));
    }

    public function status_invitations(Request $request)
    {
        $message = "Error al obtener los estados de invitacion";
        $action = "Listado de estado de invitacion";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $query = StatusInvitation::get();

            $data = $query;

            Audith::new($id_user, $action, $request->all(), 200, compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact('data'));
    }

    public function accept_invitation(Request $request)
    {
        $message = "Error al aceptar la invitación";
        $action = "Aceptar invitación de empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null; // Quien está aceptando

        try {
            $id_invitation = $request->input('id_invitation');

            $invitation = CompanyInvitation::find($id_invitation);

            if (!$invitation) {
                $response = [
                    'message' => 'No se encontró una invitación para este correo.',
                    'error_code' => 404
                ];
                Audith::new($id_user, $action, $request->all(), 422, $response);
                return response()->json($response, 422);
            }

            if ($invitation->status_id == 3) {
                $response = [
                    'message' => 'La invitación fue cancelada.',
                    'error_code' => 404
                ];
                Audith::new($id_user, $action, $request->all(), 422, $response);
                return response()->json($response, 422);
            }

            // Buscar usuario por email
            $user = User::where('email', $invitation->mail)->first();

            if (!$user) {
                $response = [
                    'message' => 'No se encontró un usuario registrado con este correo.',
                    'error_code' => 404
                ];
                Audith::new($id_user, $action, $request->all(), 422, $response);
                return response()->json($response, 422);
            }

            // Verificar si ya existe la relación user-company
            $alreadyExists = UsersCompany::where('id_user', $user->id)
                ->where('id_company_plan', $invitation->id_company_plan)
                ->exists();

            if ($alreadyExists) {
                $response = [
                    'message' => 'Este usuario ya pertenece a la empresa.',
                    'error_code' => 404
                ];
                Audith::new($id_user, $action, $request->all(), 422, $response);
                return response()->json($response, 422);
            }

            $invitation->update([
                'status_id' => 2,
            ]);

            // Crear relación users_companies
            $userCompany = UsersCompany::create([
                'id_user' => $user->id,
                'id_company_plan' => $invitation->id_company_plan,
                'id_user_company_rol' => $invitation->id_user_company_rol,
            ]);

            $userCompany->load('user', 'rol', 'plan.company.locality', 'plan.company.status', 'plan.company.category');

            $data = [
                'message' => 'Invitación aceptada exitosamente',
                'user_company' => $userCompany
            ];

            Audith::new($id_user, $action, $request->all(), 201, compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact('data'));
    }

    public function cancel_invitation($id, Request $request)
    {
        $message = "Error al cancelar la invitación";
        $action = "Cancelar invitación";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $invitation = CompanyInvitation::findOrFail($id);

            $exists = UsersCompany::whereHas('user', function ($q) use ($invitation) {
                $q->where('email', $invitation->mail);
            })->where('id_company', $invitation->id_company)->exists();

            if ($exists) {
                $response = [
                    'message' => 'El usuario ya se ha registrado.',
                    'error_code' => 400
                ];
                Audith::new($id_user, $action, $request->all(), 422, $response);
                return response()->json($response, 422);
            }

            $invitation->update([
                'status_id' => 3,
            ]);

            Audith::new($id_user, $action, ['invitation_id' => $id], 200, "Invitación cancelada");
        } catch (Exception $e) {
            Audith::new($id_user, $action, ['invitation_id' => $id], 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(['message' => 'Invitación cancelada']);
    }

    public function unassociate_user($userId, $companyId, Request $request)
    {
        $message = "Error al desasociar el usuario";
        $action = "Desasociar usuario de empresa";
        $id_user = Auth::user()->id ?? null;

        try {
            $userCompany = UsersCompany::where('id_user', $userId)
                ->where('id_company_plan', $companyId)
                ->first();

            if (!$userCompany) {
                $response = [
                    'message' => 'El usuario no está asociado a la empresa.',
                    'error_code' => 404
                ];
                Audith::new($id_user, $action, $request->all(), 422, $response);
                return response()->json($response, 422);
            }

            $user = User::find($userId);
            $userEmail = $user?->email;

            $userCompany->delete();

            if ($user) {
                $user->update(['id_plan' => 1]);

                // Actualizar todas las invitaciones relacionadas al correo y empresa
                CompanyInvitation::where('mail', $userEmail)
                    ->where('id_company_plan', $companyId)
                    ->update(['status_id' => 4]); // Estado "desasociado"
            }

            Audith::new($id_user, $action, ['id_user' => $userId, 'id_company_plan' => $companyId], 200, "Usuario desasociado");
        } catch (Exception $e) {
            Audith::new($id_user, $action, ['id_user' => $userId, 'id_company_plan' => $companyId], 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(["message" => "Usuario desasociado correctamente"]);
    }
}
