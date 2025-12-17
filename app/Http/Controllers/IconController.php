<?php

namespace App\Http\Controllers;

use App\Models\Icon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class IconController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', null);
            $search = $request->input('search', '');

            $query = Icon::query();

            if ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }

            $query->orderBy('name', 'asc');

            if ($perPage) {
                $icons = $query->paginate($perPage);
            } else {
                $icons = $query->get();
            }

            return response()->json([
                'success' => true,
                'message' => 'Iconos obtenidos exitosamente',
                'data' => $icons
            ], 200);

        } catch (Exception $e) {
            Log::error('Error al obtener iconos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los iconos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:icons,name',
                'icon' => 'required|file|mimes:svg,png,jpg,jpeg,gif|max:2048',
                'description' => 'nullable|string'
            ]);

            if (!$request->hasFile('icon')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se ha enviado ningún archivo'
                ], 400);
            }

            $file = $request->file('icon');
            $extension = $file->getClientOriginalExtension();
            $fileName = time() . '_' . uniqid() . '.' . $extension;

            $path = $file->storeAs('public/iconos', $fileName);
            $publicPath = 'storage/iconos/' . $fileName;

            $icon = Icon::create([
                'name' => $request->name,
                'file_path' => $publicPath,
                'file_name' => $fileName,
                'extension' => $extension,
                'description' => $request->description
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Icono creado exitosamente',
                'data' => $icon
            ], 201);

        } catch (Exception $e) {
            Log::error('Error al crear icono: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el icono',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $icon = Icon::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Icono obtenido exitosamente',
                'data' => $icon
            ], 200);

        } catch (Exception $e) {
            Log::error('Error al obtener icono: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el icono',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $icon = Icon::findOrFail($id);

            $request->validate([
                'name' => 'required|string|max:255|unique:icons,name,' . $id,
                'icon' => 'nullable|file|mimes:svg,png,jpg,jpeg,gif|max:2048',
                'description' => 'nullable|string'
            ]);

            if ($request->hasFile('icon')) {
                if ($icon->file_name && Storage::exists('public/iconos/' . $icon->file_name)) {
                    Storage::delete('public/iconos/' . $icon->file_name);
                }

                $file = $request->file('icon');
                $extension = $file->getClientOriginalExtension();
                $fileName = time() . '_' . uniqid() . '.' . $extension;

                $path = $file->storeAs('public/iconos', $fileName);
                $publicPath = 'storage/iconos/' . $fileName;

                $icon->file_path = $publicPath;
                $icon->file_name = $fileName;
                $icon->extension = $extension;
            }

            $icon->name = $request->name;
            $icon->description = $request->description;
            $icon->save();

            return response()->json([
                'success' => true,
                'message' => 'Icono actualizado exitosamente',
                'data' => $icon
            ], 200);

        } catch (Exception $e) {
            Log::error('Error al actualizar icono: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el icono',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $icon = Icon::findOrFail($id);

            if ($icon->file_name && Storage::exists('public/iconos/' . $icon->file_name)) {
                Storage::delete('public/iconos/' . $icon->file_name);
            }

            $icon->delete();

            return response()->json([
                'success' => true,
                'message' => 'Icono eliminado exitosamente'
            ], 200);

        } catch (Exception $e) {
            Log::error('Error al eliminar icono: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el icono',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
