<?php

namespace Database\Seeders;

use App\Models\Administrador;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Cuentas de PRUEBA (no son credenciales reales). Una por nivel de autorización.
 */
class AdministradorSeeder extends Seeder
{
    public function run(): void
    {
        $cuentas = [
            ['David Cohen', 'admin', 'admin1234', true, 3],
            ['Administrador Nivel 2', 'nivel2', 'nivel2123', true, 2],
            ['Administrador Nivel 1', 'nivel1', 'nivel1123', true, 1],
            ['Administrador Inactivo', 'inactivo', 'inactivo1234', false, 1],
        ];

        foreach ($cuentas as [$nombre, $usuario, $password, $activo, $nivel]) {
            Administrador::updateOrCreate(
                ['usuario' => $usuario],
                [
                    'nombre_completo' => $nombre,
                    'password' => Hash::make($password),
                    'activo' => $activo,
                    'nivel' => $nivel,
                ]
            );
        }
    }
}
