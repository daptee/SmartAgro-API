<?php

namespace App\Http\Controllers;

use App\Models\Audith;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class EventController extends Controller
{
    // GET - Listado de eventos con usuarios relacionados, filtro por fecha, búsqueda por nombre y paginado
    public function index(Request $request)
    {
        $message = "Error al obtener eventos";
        $action = "Listado de eventos";
        $data = null;
        $meta = null;

        try {
            $perPage = $request->get('per_page', null);
            $page = $request->get('page', 1);
            $search = $request->get('search', '');
            $date = $request->get('date', null);

            $query = Event::with(['users', 'province', 'locality'])->withCount('users');

            if ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            }

            if ($date) {
                $query->whereDate('date', $date);
            }

            $query->orderBy('name', 'asc');

            if ($perPage) {
                $events = $query->paginate($perPage, ['*'], 'page', $page);
                $data = $events->items();
                $meta = [
                    'page'      => $events->currentPage(),
                    'per_page'  => $events->perPage(),
                    'total'     => $events->total(),
                    'last_page' => $events->lastPage(),
                ];
            } else {
                $data = $query->get();
            }

            Audith::new(null, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new(null, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        if ($meta) {
            return response(compact("data", "meta"));
        }

        return response(compact("data"));
    }

    // POST - Crear nuevo evento
    public function store(Request $request)
    {
        $message = "Error al crear evento";
        $action = "Crear evento";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $request->validate([
                'name'          => 'required|string|max:255',
                'date'          => 'required|date',
                'provinces_id'  => 'required|integer|exists:provinces,id',
                'localities_id' => 'required|integer|exists:localities,id',
            ]);

            $data = Event::create([
                'name'          => $request->name,
                'date'          => $request->date,
                'provinces_id'  => $request->provinces_id,
                'localities_id' => $request->localities_id,
            ]);

            $data->load(['users', 'province', 'locality']);

            Audith::new($id_user, $action, $request->all(), 201, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"), 201);
    }

    // PUT - Actualizar evento
    public function update(Request $request, $id)
    {
        $message = "Error al actualizar evento";
        $action = "Actualizar evento";
        $id_user = Auth::user()->id ?? null;
        $data = null;

        try {
            $event = Event::findOrFail($id);

            $request->validate([
                'name'          => 'required|string|max:255',
                'date'          => 'required|date',
                'provinces_id'  => 'required|integer|exists:provinces,id',
                'localities_id' => 'required|integer|exists:localities,id',
            ]);

            $event->update([
                'name'          => $request->name,
                'date'          => $request->date,
                'provinces_id'  => $request->provinces_id,
                'localities_id' => $request->localities_id,
            ]);

            $data = $event->load(['users', 'province', 'locality']);

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // DELETE - Soft delete de evento
    public function destroy(Request $request, $id)
    {
        $message = "Error al eliminar evento";
        $action = "Eliminar evento";
        $id_user = Auth::user()->id ?? null;

        try {
            $event = Event::findOrFail($id);
            $event->delete();

            Audith::new($id_user, $action, $request->all(), 200, ['deleted_id' => $id]);

        } catch (Exception $e) {
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(["message" => "Evento eliminado correctamente"]);
    }
}
