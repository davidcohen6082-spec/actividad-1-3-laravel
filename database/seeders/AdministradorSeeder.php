<?php

namespace Database\Seeders;

use App\Models\Administrador;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdministradorSeeder extends Seeder
{
    public function run(): void
    {
        Administrador::create([
            'nombre_completo' => 'David Cohen',
            'usuario' => 'admin',
            'password' => Hash::make('admin1234'),
            'activo' => true,
        ]);

        Administrador::create([
            'nombre_completo' => 'Administrador Inactivo',
            'usuario' => 'inactivo',
            'password' => Hash::make('inactivo1234'),
            'activo' => false,
        ]);
    }
}