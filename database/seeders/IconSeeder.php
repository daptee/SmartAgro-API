<?php

namespace Database\Seeders;

use App\Models\Icon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class IconSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $iconsPath = public_path('storage/iconos');

        if (!File::exists($iconsPath)) {
            $this->command->error('El directorio public/storage/iconos no existe');
            return;
        }

        $files = File::files($iconsPath);

        foreach ($files as $file) {
            $fileName = $file->getFilename();
            $extension = $file->getExtension();

            if (in_array($extension, ['svg', 'png', 'jpg', 'jpeg', 'gif'])) {
                $name = pathinfo($fileName, PATHINFO_FILENAME);

                $existingIcon = Icon::where('file_name', $fileName)->first();

                if (!$existingIcon) {
                    Icon::create([
                        'name' => $name,
                        'file_path' => 'storage/iconos/' . $fileName,
                        'file_name' => $fileName,
                        'extension' => $extension,
                        'description' => null
                    ]);

                    $this->command->info("Icono cargado: {$name}");
                } else {
                    $this->command->warn("Icono ya existe: {$name}");
                }
            }
        }

        $this->command->info('Proceso de carga de iconos completado');
    }
}
