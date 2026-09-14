<?php

namespace Database\Seeders;

use App\Models\Orden;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class OrdenSeeder extends Seeder
{
    public function run(): void
    {
        $usuarioIds = Usuario::pluck('id');

        for ($i = 1; $i <= 15; $i++) {
            Orden::create([
                'usuario_id' => $usuarioIds->random(),
                'estado' => fake()->randomElement(['pendiente', 'completada', 'cancelada']),
                'total' => 0,
                'fecha' => fake()->dateTimeBetween('-3 months', 'now'),
            ]);
        }
    }
}