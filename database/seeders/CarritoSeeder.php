<?php

namespace Database\Seeders;

use App\Models\Carrito;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class CarritoSeeder extends Seeder
{
    public function run(): void
    {
        Usuario::all()->each(function ($usuario) {
            Carrito::firstOrCreate(['usuario_id' => $usuario->id]);
        });
    }
}