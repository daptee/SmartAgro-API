<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ReportImageController extends Controller
{
    private string $storagePath = 'storage/report/images';

    private function getPublicPath(): string
    {
        return public_path($this->storagePath);
    }

    private function ensureDirectoryExists(): void
    {
        $path = $this->getPublicPath();
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }
    }

    public function index()
    {
        try {
            $this->ensureDirectoryExists();

            $files = File::files($this->getPublicPath());

            $images = collect($files)->map(function ($file) {
                return [
                    'file_name'  => $file->getFilename(),
                    'file_path'  => $this->storagePath . '/' . $file->getFilename(),
                    'url'        => url($this->storagePath . '/' . $file->getFilename()),
                    'extension'  => $file->getExtension(),
                    'size'       => $file->getSize(),
                    'created_at' => date('Y-m-d H:i:s', $file->getCTime()),
                ];
            })->values();

            return response()->json([
                'success' => true,
                'message' => 'Imágenes obtenidas exitosamente',
                'data'    => $images,
                'total'   => $images->count(),
            ], 200);

        } catch (Exception $e) {
            Log::error('Error al obtener imágenes de report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las imágenes',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|file|mimes:png,jpg,jpeg,gif,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Alguna de las validaciones falló',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $this->ensureDirectoryExists();

            $file      = $request->file('image');
            $extension = $file->getClientOriginalExtension();
            $fileName  = time() . '_' . uniqid() . '.' . $extension;

            $file->move($this->getPublicPath(), $fileName);

            return response()->json([
                'success'   => true,
                'message'   => 'Imagen subida exitosamente',
                'data'      => [
                    'file_name' => $fileName,
                    'file_path' => $this->storagePath . '/' . $fileName,
                    'url'       => url($this->storagePath . '/' . $fileName),
                    'extension' => $extension,
                ],
            ], 201);

        } catch (Exception $e) {
            Log::error('Error al subir imagen de report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al subir la imagen',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, string $fileName)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|file|mimes:png,jpg,jpeg,gif,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Alguna de las validaciones falló',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $oldPath = $this->getPublicPath() . '/' . $fileName;

            if (!File::exists($oldPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Imagen no encontrada',
                ], 404);
            }

            File::delete($oldPath);

            $file         = $request->file('image');
            $extension    = $file->getClientOriginalExtension();
            $newFileName  = time() . '_' . uniqid() . '.' . $extension;

            $file->move($this->getPublicPath(), $newFileName);

            return response()->json([
                'success' => true,
                'message' => 'Imagen reemplazada exitosamente',
                'data'    => [
                    'file_name' => $newFileName,
                    'file_path' => $this->storagePath . '/' . $newFileName,
                    'url'       => url($this->storagePath . '/' . $newFileName),
                    'extension' => $extension,
                ],
            ], 200);

        } catch (Exception $e) {
            Log::error('Error al actualizar imagen de report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la imagen',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(string $fileName)
    {
        try {
            $path = $this->getPublicPath() . '/' . $fileName;

            if (!File::exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Imagen no encontrada',
                ], 404);
            }

            File::delete($path);

            return response()->json([
                'success' => true,
                'message' => 'Imagen eliminada exitosamente',
            ], 200);

        } catch (Exception $e) {
            Log::error('Error al eliminar imagen de report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la imagen',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
