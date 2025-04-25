<?php

namespace App\Http\Controllers;

use App\Models\Audith;
use App\Models\Company;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function store(Request $request)
    {
        $message = "Error al crear empresa";
        $action = "Crear empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $request->validate([
                'company_name' => 'required|unique:companies,company_name',
                'cuit' => 'required|unique:companies,cuit',
                'email' => 'required|email|unique:companies,email',
                'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
                'main_color' => 'nullable|string|max:255',
                'secondary_color' => 'nullable|string|max:255',
                'id_locality' => 'nullable|exists:localities,id',
                'id_company_category' => 'nullable|exists:company_categories,id',
                'range_number_of_employees' => 'nullable|string|max:255',
                'website' => 'nullable|url|max:255',
                'status' => 'required|in:1,2',
            ]);

            $imagePath = public_path('storage/company/logo/'); // Ruta en public

            // Crear la carpeta si no existe
            if (!file_exists($imagePath)) {
                mkdir($imagePath, 0777, true);
            }

            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logo = $request->file('logo');
                $logoName = time() . '_logo_' . $logo->getClientOriginalName();
                $logo->move($imagePath, $logoName);
                $logoPath = '/storage/company/logo/' . $logoName;
            }

            $data = Company::create([
                'company_name' => $request->company_name,
                'cuit' => $request->cuit,
                'email' => $request->email,
                'logo' => $logoPath,
                'main_color' => $request->main_color,
                'secondary_color' => $request->secondary_color,
                'id_locality' => $request->id_locality,
                'id_company_category' => $request->id_company_category,
                'range_number_of_employees' => $request->range_number_of_employees,
                'website' => $request->website,
                'status_id' => $request->status,
            ]);

            $data->load([
                'locality.province',
                'category',
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
        $message = "Error al actualizar empresa";
        $action = "Actualizar empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $company = Company::findOrFail($id);

            $request->validate([
                'company_name' => 'required|unique:companies,company_name,' . $company->id,
                'cuit' => 'required|unique:companies,cuit,' . $company->id,
                'email' => 'required|email|unique:companies,email,' . $company->id,
                'logo' => 'nullable',
                'main_color' => 'nullable|string|max:255',
                'secondary_color' => 'nullable|string|max:255',
                'id_locality' => 'nullable|exists:localities,id',
                'id_company_category' => 'nullable|exists:company_categories,id',
                'range_number_of_employees' => 'nullable|string|max:255',
                'website' => 'nullable|url|max:255',
                'status' => 'required|in:1,2',
            ]);

            $imagePath = public_path('storage/company/logo/');

            // Crear carpeta si no existe
            if (!file_exists($imagePath)) {
                mkdir($imagePath, 0777, true);
            }

            if ($request->hasFile('logo')) {
                // Eliminar imagen anterior
                if ($company->logo && file_exists(public_path($company->logo))) {
                    unlink(public_path($company->logo));
                }

                // Guardar nueva imagen
                $logo = $request->file('logo');
                $logoName = time() . '_logo_' . $logo->getClientOriginalName();
                $logo->move($imagePath, $logoName);
                $company->logo = '/storage/company/logo/' . $logoName;
            } elseif ($request->logo === null) {
                // Si se manda explícitamente null
                if ($company->logo && file_exists(public_path($company->logo))) {
                    unlink(public_path($company->logo));
                }
                $company->logo = null;
            }
            // Si se manda string se ignora el campo logo

            // Actualizar campos
            $company->update([
                'company_name' => $request->company_name,
                'cuit' => $request->cuit,
                'email' => $request->email,
                'main_color' => $request->main_color,
                'secondary_color' => $request->secondary_color,
                'id_locality' => $request->id_locality,
                'id_company_category' => $request->id_company_category,
                'range_number_of_employees' => $request->range_number_of_employees,
                'website' => $request->website,
                'status_id' => $request->status,
            ]);

            $company->save();

            $company->load([
                'locality.province',
                'category',
                'status'
            ]);

            $data = $company;
            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function index(Request $request)
    {
        $message = "Error al obtener las empresas";
        $action = "Listado de empresas";
        $data = null;
        $id_user = Auth::user()->id ?? null;
        try {
            $perPage = $request->query('per_page', 10);
            $page = $request->query('page', 1);
            $province = $request->query('province');
            $localy = $request->query('localy');
            $category = $request->query('category');
            $status = $request->query('status');
            $search = $request->query('search');

            // Iniciar consulta con relaciones
            $query = Company::with(['category', 'locality.province', 'status']);

            // Filtros
            if (!is_null($province)) {
                $query->whereHas('locality.province', function ($q) use ($province) {
                    $q->where('name', $province);
                });
            }

            if (!is_null($localy)) {
                $query->whereHas('locality', function ($q) use ($localy) {
                    $q->where('name', $localy);
                });
            }

            if (!is_null($category)) {
                $query->whereHas('category', function ($q) use ($category) {
                    $q->where('name', $category);
                });
            }

            if (!is_null($status)) {
                $query->whereHas('status', function ($q) use ($status) {
                    $q->where('id', $status);
                });
            }

            if (!is_null($search)) {
                $query->where('company_name', 'like', '%' . $search . '%');
            }

            // Paginación
            $companies = $query->paginate($perPage, ['*'], 'page', $page);

            // Formato de respuesta
            $data = [
                'result' => $companies->items(),
                'meta_data' => [
                    'page' => $companies->currentPage(),
                    'per_page' => $companies->perPage(),
                    'total' => $companies->total(),
                    'last_page' => $companies->lastPage(),
                ]
            ];
            
            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));

    }

    public function show(Request $request, $id)
    {
        $message = "Error al obtener la empresa";
        $action = "Empresa";
        $data = null;
        $id_user = Auth::user()->id ?? null;
        try{
            $company = Company::with(['category', 'locality.province', 'status'])->findOrFail($id);
            $data = $company;
        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }
}

