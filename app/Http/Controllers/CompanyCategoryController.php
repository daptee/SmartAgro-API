<?php

namespace App\Http\Controllers;

use App\Models\CompanyCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\Audith;
use Exception;

class CompanyCategoryController extends Controller
{

    public function index(Request $request)
    {
        $message = "Error al obtener registros";
        $action = "Listado de categorías de empresas";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $data = CompanyCategory::all();

            $data->load([
                'status'
            ]);
            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }
    
    public function store(Request $request)
    {
        $message = "Error al crear categoría";
        $action = "Crear categoría de empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $request->validate([
                'name' => 'required|unique:company_categories,name'
            ]);

            $data = CompanyCategory::create([
                'name' => $request->name,
                'status_id' => 1
            ]);

            $data->load([
                'status'
            ]);

            Audith::new($id_user, $action, $request->all(), 201, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"), 201);
    }

    public function update(Request $request, $id)
    {
        $message = "Error al actualizar categoría";
        $action = "Actualizar categoría de empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $category = CompanyCategory::findOrFail($id);

            $request->validate([
                'name' => [
                    'required',
                    Rule::unique('company_categories')->ignore($category->id)
                ],
                'status' => 'required|exists:status,id',
            ]);

            $category->update([
                'name' => $request->name,
                'status_id' => $request->status,
            ]);
            $data = $category;

            $data->load([
                'status'
            ]);

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }
}

