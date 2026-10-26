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
            'nivel' => 3,
        ]);

        Administrador::create([
            'nombre_completo' => 'Administrador Nivel 2',
            'usuario' => 'nivel2',
            'password' => Hash::make('nivel2123'),
            'activo' => true,
            'nivel' => 2,
        ]);

        Administrador::create([
            'nombre_completo' => 'Administrador Nivel 1',
            'usuario' => 'nivel1',
            'password' => Hash::make('nivel1123'),
            'activo' => true,
            'nivel' => 1,
        ]);

        Administrador::create([
            'nombre_completo' => 'Administrador Inactivo',
            'usuario' => 'inactivo',
            'password' => Hash::make('inactivo1234'),
            'activo' => false,
            'nivel' => 1,
        ]);
    }
}