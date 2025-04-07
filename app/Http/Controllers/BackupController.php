<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class BackupController extends Controller
{
    public function createBackup()
    {
        $database = config('services.data_base.database');
        $username = config('services.data_base.username');
        $password = config('services.data_base.password');
        $host = config('services.data_base.host');

        $storagePath = public_path('storage/backups'); // Usamos storage en lugar de public
        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        $fileName = "backup_" . Carbon::now()->format('Y_m_d_His') . ".sql";
        $filePath = $storagePath . '/' . $fileName;

        // Comando para hacer el backup
        $dumpCommand = "mysqldump -h {$host} -u {$username} --password={$password} {$database} > {$filePath}";

        $process = Process::fromShellCommandline($dumpCommand);
        $process->run();

        // Verificar si ocurrió algún error
        if (!$process->isSuccessful()) {
            return response()->json(['error' => 'Error al crear el backup'], 500);
        }

        return response()->json([
            'message' => 'Backup creado con éxito',
        ]);
    }
}
