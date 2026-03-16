<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Audith;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Exception;

class FaqController extends Controller
{
    // GET ALL - Público (sin token)
    public function index(Request $request)
    {
        $message = "Error al obtener FAQs";
        $action = "Listado de FAQs públicas";
        $data = null;

        try {
            $data = Faq::where('status_id', 1)->get();

            $data->load(['status']);

            Audith::new(null, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new(null, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // POST - Requiere token
    public function store(Request $request)
    {
        $message = "Error al crear FAQ";
        $action = "Crear FAQ";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $request->validate([
                'question' => 'required|string|max:255',
                'answer' => 'required|string'
            ]);

            $data = Faq::create([
                'question' => $request->question,
                'answer' => $request->answer,
                'status_id' => 1
            ]);

            $data->load(['status']);

            Audith::new($id_user, $action, $request->all(), 201, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"), 201);
    }

    // PUT - Requiere token
    public function update(Request $request, $id)
    {
        $message = "Error al actualizar FAQ";
        $action = "Actualizar FAQ";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $faq = Faq::findOrFail($id);

            $request->validate([
                'question' => 'required|string|max:255',
                'answer' => 'required|string',
                'status' => 'required|exists:status,id',
            ]);

            $faq->update([
                'question' => $request->question,
                'answer' => $request->answer,
                'status_id' => $request->status,
            ]);

            $data = $faq;
            $data->load(['status']);

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // DELETE - Requiere token
    public function destroy(Request $request, $id)
    {
        $message = "Error al eliminar FAQ";
        $action = "Eliminar FAQ";
        $id_user = Auth::user()->id ?? null;

        try {
            $faq = Faq::findOrFail($id);
            $faq->delete();

            Audith::new($id_user, $action, $request->all(), 200, ['deleted_id' => $id]);

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(["message" => "FAQ eliminada correctamente"]);
    }
}