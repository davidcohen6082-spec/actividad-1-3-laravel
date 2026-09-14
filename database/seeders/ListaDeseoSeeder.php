<?php

namespace Database\Seeders;

use App\Models\ListaDeseo;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class ListaDeseoSeeder extends Seeder
{
    public function run(): void
    {
        $usuarioIds = Usuario::pluck('id');
        $productoIds = Producto::pluck('id');

        for ($i = 1; $i <= 25; $i++) {
            ListaDeseo::firstOrCreate([
                'usuario_id' => $usuarioIds->random(),
                'producto_id' => $productoIds->random(),
            ], [
                'fecha_agregado' => fake()->dateTimeBetween('-2 months', 'now'),
            ]);
        }
    }
}