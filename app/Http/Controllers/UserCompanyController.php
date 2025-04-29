<?php

namespace App\Http\Controllers;

use App\Mail\InvitationUserCompanyMailable;
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

            $query = UsersCompany::with(['user', 'company', 'role']);

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

    public function send_invitation(Request $request)
    {
        $message = "Error al enviar la invitación";
        $action = "Enviar invitación a empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $request->validate([
                'mail' => 'required|email',
                'name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'id_company' => 'required|exists:companies,id',
                'id_user_company_rol' => 'required|exists:users_company_roles,id'
            ]);

            $data = CompanyInvitation::create([
                'id_company' => $request->id_company,
                'mail' => $request->mail,
                'id_user_company_rol' => $request->id_user_company_rol,
                'invitation_date' => Carbon::now(),
            ]);

            $data->load('rol', 'company.locality', 'company.status', 'company.category');
            $company = $data['company'];
            $new_user = [
                "name" => $request->name,
                "last_name" => $request->last_name,
                "email" => $request->mail,
            ];

            Log::info($new_user);
            Log::info($company);
            Log::info($data);
            Mail::to($request->mail)->send(new InvitationUserCompanyMailable($new_user, $company, $data));

            Audith::new($id_user, $action, $request->all(), 201, compact('data'));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact('data'), 201);
    }

    public function list_invitations(Request $request)
    {
        $message = "Error al obtener las invitaciones";
        $action = "Listado de invitaciones de empresas";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $company = $request->query('company');

            $query = CompanyInvitation::with('rol', 'company.locality', 'company.status', 'company.category');

            // Aplicar filtro si llega el parámetro 'company'
            if (!is_null($company)) {
                $query->where('id_company', $company);
            }

            $results = $query->get();

            $data = $results;

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

            Log::info('invitacionnnn: ', ['invitacion'=> $invitation->id]);
            Log::info($invitation->mail);

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
                ->where('id_company', $invitation->id_company)
                ->exists();

            if ($alreadyExists) {
                $response = [
                    'message' => 'Este usuario ya pertenece a la empresa.',
                    'error_code' => 404
                ];
                Audith::new($id_user, $action, $request->all(), 422, $response);
                return response()->json($response, 422);
            }

            // Crear relación users_companies
            $userCompany = UsersCompany::create([
                'id_user' => $user->id,
                'id_company' => $invitation->id_company,
                'id_user_company_rol' => $invitation->id_user_company_rol,
            ]);

            $userCompany->load('user', 'role', 'company.locality', 'company.status', 'company.category');

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

            $invitation->delete();

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
                ->where('id_company', $companyId)
                ->first();

            if (!$userCompany) {
                $response = [
                    'message' => 'El usuario no está asociado a la empresa.',
                    'error_code' => 404
                ];
                Audith::new($id_user, $action, $request->all(), 422, $response);
                return response()->json($response, 422);
            }

            $userCompany->delete();

            $user = User::find($userId);
            if ($user) {
                $user->update(['id_plan' => 1]);
            }

            Audith::new($id_user, $action, ['id_user' => $userId, 'id_company' => $companyId], 200, "Usuario desasociado");
        } catch (Exception $e) {
            Audith::new($id_user, $action, ['id_user' => $userId, 'id_company' => $companyId], 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(["message" => "Usuario desasociado correctamente"]);
    }
}
