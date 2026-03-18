<?php

namespace App\Http\Controllers;

use App\Models\Audith;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class EventImportController extends Controller
{
    private function toCamelCase(string $value): string
    {
        return mb_convert_case(mb_strtolower($value), MB_CASE_TITLE, 'UTF-8');
    }

    // POST - Importar usuarios desde un archivo Excel y asignarles contraseña y event_id
    public function import(Request $request)
    {
        $action = "Importación de usuarios desde Excel";
        $id_user = Auth::user()->id ?? null;

        $request->validate([
            'file'     => 'required|file|mimes:xlsx,xls,csv',
            'password' => 'required|string|min:6',
            'event_id' => 'nullable|integer|exists:events,id',
        ]);

        try {
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            // Eliminar la primera fila (cabecera)
            array_shift($rows);

            $created = [];
            $skipped = [];

            DB::beginTransaction();

            foreach ($rows as $row) {
                // Columnas del Excel:
                // A=name, B=email, C=phone, D=company (ignorar),
                // E=province_id, F=locality_id, G=profile_id, H=consent (ignorar), I=createdAt (ignorar)
                $fullName      = trim($row['A'] ?? '');
                $email         = strtolower(trim($row['B'] ?? ''));
                $phone         = trim($row['C'] ?? '') ?: null;
                $province_id   = trim($row['E'] ?? '') ?: null;
                $locality_id   = trim($row['F'] ?? '') ?: null;
                $profile_id    = trim($row['G'] ?? '') ?: null;
                $createdAt     = trim($row['I'] ?? '') ?: null;

                // Saltar filas vacías o sin email
                if (empty($fullName) || empty($email)) {
                    $skipped[] = $email ?: '(fila vacía)';
                    continue;
                }

                // Saltar si el email ya existe
                if (User::where('email', $email)->exists()) {
                    $skipped[] = $email;
                    continue;
                }

                // Separar nombre y apellido: la primera palabra es el nombre, el resto es apellido
                $parts     = explode(' ', $fullName, 2);
                $name      = $this->toCamelCase($parts[0] ?? '');
                $last_name = $this->toCamelCase($parts[1] ?? '');

                $user = User::create([
                    'name'              => $name,
                    'last_name'         => $last_name,
                    'email'             => $email,
                    'phone'             => $phone,
                    'password'          => $request->password,
                    'event_id'          => $request->event_id,
                    'id_locality'       => $locality_id,
                    'id_user_profile'   => $profile_id,
                    'id_status'         => 1,
                    'id_plan'           => 1,
                    'email_confirmation' => $createdAt ? \Carbon\Carbon::parse($createdAt) : null,
                ]);

                $created[] = $user->email;
            }

            DB::commit();

            $data = [
                'created_count' => count($created),
                'skipped_count' => count($skipped),
                'created'       => $created,
                'skipped'       => $skipped,
            ];

            Audith::new($id_user, $action, ['event_id' => $request->event_id], 201, $data);

            return response()->json([
                'message' => 'Importación completada',
                'data'    => $data,
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            $response = [
                'message' => 'Error al importar usuarios',
                'error'   => $e->getMessage(),
                'line'    => $e->getLine(),
            ];
            Log::error($response);
            Audith::new($id_user, $action, $request->all(), 500, $response);
            return response()->json($response, 500);
        }
    }
}
