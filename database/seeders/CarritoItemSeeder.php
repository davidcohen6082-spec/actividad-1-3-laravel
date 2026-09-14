<?php

namespace Database\Seeders;

use App\Models\Carrito;
use App\Models\CarritoItem;
use App\Models\Producto;
use Illuminate\Database\Seeder;

class CarritoItemSeeder extends Seeder
{
    public function run(): void
    {
        $carritoIds = Carrito::pluck('id');
        $productoIds = Producto::pluck('id');

        for ($i = 1; $i <= 25; $i++) {
            CarritoItem::firstOrCreate([
                'carrito_id' => $carritoIds->random(),
                'producto_id' => $productoIds->random(),
            ], [
                'cantidad' => fake()->numberBetween(1, 3),
            ]);
        }
    }
}