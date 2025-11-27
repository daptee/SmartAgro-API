<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\Audith;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Exception;

class NewsController extends Controller
{
    // GET ALL - Retorna todas las noticias con filtros
    public function index(Request $request)
    {
        $message = "Error al obtener noticias";
        $action = "Listado de noticias";
        $data = null;

        try {
            $query = News::query();

            // Filtro por rango de fechas
            if ($request->has('date_from') && $request->date_from) {
                $query->where('date', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->where('date', '<=', $request->date_to);
            }

            // Filtro por estado
            if ($request->has('status_id') && $request->status_id) {
                $query->where('status_id', $request->status_id);
            }

            // Filtro por plan
            if ($request->has('id_plan') && $request->id_plan) {
                $query->where('id_plan', $request->id_plan);
            }

            // Campo de búsqueda por título o contenido
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                      ->orWhere('new', 'LIKE', "%{$search}%");
                });
            }

            $data = $query->with(['plan', 'status', 'user'])->get();

            Audith::new(Auth::user()->id ?? null, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new(Auth::user()->id ?? null, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // POST - Crear nueva noticia
    public function store(Request $request)
    {
        $message = "Error al crear noticia";
        $action = "Crear noticia";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            // Validaciones según el estado
            $rules = [
                'title' => 'required|string|max:255',
                'date' => 'required|date',
                'status_id' => 'required|in:1,2', // 1=Publicado, 2=Borrador
            ];

            // Si el estado es PUBLICADO (1), todos los campos son obligatorios
            if ($request->status_id == 1) {
                $rules['new'] = 'required|string';
                $rules['img'] = 'required|string';
                $rules['id_plan'] = 'required|exists:plans,id';
            } else {
                // Si es BORRADOR (2), estos campos son opcionales
                $rules['new'] = 'nullable|string';
                $rules['img'] = 'nullable|string';
                $rules['id_plan'] = 'nullable|exists:plans,id';
            }

            $request->validate($rules);

            $data = News::create([
                'title' => $request->title,
                'new' => $request->new,
                'img' => $request->img,
                'date' => $request->date,
                'id_plan' => $request->id_plan,
                'status_id' => $request->status_id,
                'id_user' => $id_user,
            ]);

            $data->load(['plan', 'status', 'user']);

            Audith::new($id_user, $action, $request->all(), 201, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"), 201);
    }

    // PUT - Editar noticia
    public function update(Request $request, $id)
    {
        $message = "Error al actualizar noticia";
        $action = "Actualizar noticia";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $news = News::findOrFail($id);

            // Validaciones según el estado
            $rules = [
                'title' => 'required|string|max:255',
                'date' => 'required|date',
                'status_id' => 'required|in:1,2',
            ];

            // Si el estado es PUBLICADO (1), todos los campos son obligatorios
            if ($request->status_id == 1) {
                $rules['new'] = 'required|string';
                $rules['img'] = 'required|string';
                $rules['id_plan'] = 'required|exists:plans,id';
            } else {
                // Si es BORRADOR (2), estos campos son opcionales
                $rules['new'] = 'nullable|string';
                $rules['img'] = 'nullable|string';
                $rules['id_plan'] = 'nullable|exists:plans,id';
            }

            $request->validate($rules);

            $news->update([
                'title' => $request->title,
                'new' => $request->new,
                'img' => $request->img,
                'date' => $request->date,
                'id_plan' => $request->id_plan,
                'status_id' => $request->status_id,
                'id_user' => $id_user,
            ]);

            $data = $news;
            $data->load(['plan', 'status', 'user']);

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // PUT - Cambiar estado de la noticia
    public function changeStatus(Request $request, $id)
    {
        $message = "Error al cambiar estado de noticia";
        $action = "Cambiar estado de noticia";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $news = News::findOrFail($id);

            $request->validate([
                'status_id' => 'required|in:1,2',
            ]);

            // Si se cambia a PUBLICADO (1), validar que todos los datos estén completos
            if ($request->status_id == 1) {
                if (empty($news->title) || empty($news->new) || empty($news->img) || empty($news->date) || empty($news->id_plan)) {
                    return response([
                        "message" => "No se puede publicar la noticia. Todos los campos deben estar completos (título, contenido, imagen, fecha y plan)."
                    ], 400);
                }
            }

            $news->update([
                'status_id' => $request->status_id,
            ]);

            $data = $news;
            $data->load(['plan', 'status', 'user']);

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // DELETE - Soft delete de la noticia
    public function destroy(Request $request, $id)
    {
        $message = "Error al eliminar noticia";
        $action = "Eliminar noticia";
        $id_user = Auth::user()->id ?? null;

        try {
            $news = News::findOrFail($id);
            $news->delete(); // Soft delete

            Audith::new($id_user, $action, $request->all(), 200, ['deleted_id' => $id]);

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(["message" => "Noticia eliminada correctamente"]);
    }
}
