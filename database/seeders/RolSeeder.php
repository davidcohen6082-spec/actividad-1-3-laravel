<?php

namespace Database\Seeders;

use App\Models\Rol;
use Illuminate\Database\Seeder;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'administrador', 'cliente', 'moderador', 'soporte', 'editor',
            'invitado', 'voluntario', 'coordinador', 'supervisor', 'colaborador',
        ];

        foreach ($roles as $nombre) {
            Rol::firstOrCreate(['nombre' => $nombre]);
        }
    }
}