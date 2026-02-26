<?php

namespace App\Http\Controllers;

use App\Models\Image;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', null);
            $search = $request->input('search', '');

            $query = Image::query();

            if ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }

            $query->orderBy('name', 'asc');

            if ($perPage) {
                $images = $query->paginate($perPage);
            } else {
                $images = $query->get();
            }

            return response()->json([
                'success' => true,
                'message' => 'Imágenes obtenidas exitosamente',
                'data' => $images
            ], 200);

        } catch (Exception $e) {
            Log::error('Error al obtener imágenes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las imágenes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name'        => 'required|string|max:255|unique:images,name',
                'image'       => 'required|file|mimes:png,jpg,jpeg,gif,webp|max:5120',
                'description' => 'nullable|string'
            ]);

            if (!$request->hasFile('image')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se ha enviado ningún archivo'
                ], 400);
            }

            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();
            $fileName = time() . '_' . uniqid() . '.' . $extension;

            $file->storeAs('public/imagenes', $fileName);
            $publicPath = 'storage/imagenes/' . $fileName;

            $image = Image::create([
                'name'        => $request->name,
                'file_path'   => $publicPath,
                'file_name'   => $fileName,
                'extension'   => $extension,
                'description' => $request->description
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Imagen creada exitosamente',
                'data' => $image
            ], 201);

        } catch (Exception $e) {
            Log::error('Error al crear imagen: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la imagen',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $image = Image::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Imagen obtenida exitosamente',
                'data' => $image
            ], 200);

        } catch (Exception $e) {
            Log::error('Error al obtener imagen: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la imagen',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $image = Image::findOrFail($id);

            $request->validate([
                'name'        => 'required|string|max:255|unique:images,name,' . $id,
                'image'       => 'nullable|file|mimes:png,jpg,jpeg,gif,webp|max:5120',
                'description' => 'nullable|string'
            ]);

            if ($request->hasFile('image')) {
                if ($image->file_name && Storage::exists('public/imagenes/' . $image->file_name)) {
                    Storage::delete('public/imagenes/' . $image->file_name);
                }

                $file = $request->file('image');
                $extension = $file->getClientOriginalExtension();
                $fileName = time() . '_' . uniqid() . '.' . $extension;

                $file->storeAs('public/imagenes', $fileName);
                $publicPath = 'storage/imagenes/' . $fileName;

                $image->file_path = $publicPath;
                $image->file_name = $fileName;
                $image->extension = $extension;
            }

            $image->name = $request->name;
            $image->description = $request->description;
            $image->save();

            return response()->json([
                'success' => true,
                'message' => 'Imagen actualizada exitosamente',
                'data' => $image
            ], 200);

        } catch (Exception $e) {
            Log::error('Error al actualizar imagen: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la imagen',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $image = Image::findOrFail($id);

            if ($image->file_name && Storage::exists('public/imagenes/' . $image->file_name)) {
                Storage::delete('public/imagenes/' . $image->file_name);
            }

            $image->delete();

            return response()->json([
                'success' => true,
                'message' => 'Imagen eliminada exitosamente'
            ], 200);

        } catch (Exception $e) {
            Log::error('Error al eliminar imagen: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la imagen',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
