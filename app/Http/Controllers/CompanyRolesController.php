<?php

namespace App\Http\Controllers;

use App\Models\CompanyRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Audith;
use Illuminate\Validation\Rule;
use Exception;

class CompanyRolesController extends Controller
{
    public function index()
    {
        try {
            $data = CompanyRole::all();
            $data->load([
                'status'
            ]);
            return response(compact('data'));
        } catch (Exception $e) {
            return response(['message' => 'Error al obtener los roles', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $message = "Error al crear rol de usuario empresa";
        $action = "Crear rol de usuario empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $request->validate([
                'name' => 'required|unique:users_company_roles,name',
                'status' => 'required|in:1,2',
            ]);

            $data = CompanyRole::create([
                'name' => $request->name,
                'status_id' => $request->status,
            ]);

            $data->load([
                'status'
            ]);

            Audith::new($id_user, $action, $request->all(), 201, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(['message' => $message, 'error' => $e->getMessage()], 500);
        }

        return response(compact('data'), 201);
    }

    public function update(Request $request, $id)
    {
        $message = "Error al actualizar rol de usuario empresa";
        $action = "Actualizar rol de usuario empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $role = CompanyRole::findOrFail($id);

            $request->validate([
                'name' => [
                    'required',
                    Rule::unique('users_company_roles')->ignore($role->id),
                ],
                'status' => 'required|in:1,2',
            ]);

            $role->update([
                'name' => $request->name,
                'status_id' => $request->status,
            ]);

            $data = $role;
            
            $data->load([
                'status'
            ]);

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(['message' => $message, 'error' => $e->getMessage()], 500);
        }

        return response(compact('data'));
    }
}
