<?php

namespace App\Http\Controllers;

use App\Models\Audith;
use App\Models\Product;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    // GET ALL
    public function index(Request $request)
    {
        $message = "Error al obtener productos";
        $action = "Listado de productos";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $perPage = $request->input('per_page', null);
            $search = $request->input('search', '');
            $id_classification = $request->input('id_classification');

            $query = Product::with(['classification'])->orderBy('name', 'asc');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if ($id_classification) {
                $query->where('id_classification', $id_classification);
            }

            if ($perPage) {
                $data = $query->paginate($perPage);
            } else {
                $data = $query->get();
            }

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // GET ONE
    public function show(Request $request, $id)
    {
        $message = "Error al obtener producto";
        $action = "Obtener producto";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $data = Product::with(['classification'])->findOrFail($id);

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // POST
    public function store(Request $request)
    {
        $message = "Error al crear producto";
        $action = "Crear producto";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $request->validate([
                'name'              => 'required|string|max:255',
                'description'       => 'nullable|string',
                'id_classification' => 'required|exists:classifications,id',
            ]);

            $data = Product::create([
                'name'              => $request->name,
                'description'       => $request->description,
                'id_classification' => $request->id_classification,
                'status'            => true,
            ]);

            $data->load(['classification']);

            Audith::new($id_user, $action, $request->all(), 201, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"), 201);
    }

    // PUT
    public function update(Request $request, $id)
    {
        $message = "Error al actualizar producto";
        $action = "Actualizar producto";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $product = Product::findOrFail($id);

            $request->validate([
                'name'              => 'required|string|max:255',
                'description'       => 'nullable|string',
                'id_classification' => 'required|exists:classifications,id',
            ]);

            $product->update([
                'name'              => $request->name,
                'description'       => $request->description,
                'id_classification' => $request->id_classification,
            ]);

            $data = $product;
            $data->load(['classification']);

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // PUT STATUS
    public function updateStatus(Request $request, $id)
    {
        $message = "Error al actualizar estado de producto";
        $action = "Actualizar estado de producto";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $product = Product::findOrFail($id);

            $request->validate([
                'status' => 'required|boolean',
            ]);

            $product->update(['status' => $request->status]);

            $data = $product;
            $data->load(['classification']);

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // DELETE (soft delete)
    public function destroy(Request $request, $id)
    {
        $message = "Error al eliminar producto";
        $action = "Eliminar producto";
        $id_user = Auth::user()->id ?? null;

        try {
            $product = Product::findOrFail($id);
            $product->delete();

            Audith::new($id_user, $action, $request->all(), 200, ['deleted_id' => $id]);

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(["message" => "Producto eliminado correctamente"]);
    }
}
