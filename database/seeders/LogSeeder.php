<?php

namespace Database\Seeders;

use App\Models\Log as LogModel;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class LogSeeder extends Seeder
{
    public function run(): void
    {
        $usuarioIds = Usuario::pluck('id');

        $acciones = [
            'Publicó un nuevo producto', 'Actualizó su perfil', 'Agregó un producto a favoritos',
            'Completó una orden', 'Inició sesión', 'Eliminó un producto de su carrito',
            'Modificó el stock de un producto', 'Consultó el catálogo',
        ];

        for ($i = 1; $i <= 15; $i++) {
            LogModel::create([
                'usuario_id' => $usuarioIds->random(),
                'accion' => fake()->randomElement($acciones),
                'fecha' => fake()->dateTimeBetween('-2 months', 'now'),
            ]);
        }
    }
}