<?php

namespace Database\Seeders;

use App\Models\Orden;
use App\Models\OrdenDetalle;
use App\Models\Producto;
use Illuminate\Database\Seeder;

class OrdenDetalleSeeder extends Seeder
{
    public function run(): void
    {
        $productos = Producto::all();

        Orden::all()->each(function ($orden) use ($productos) {
            $items = $productos->random(rand(1, 3));
            $total = 0;

            foreach ($items as $producto) {
                $cantidad = fake()->numberBetween(1, 2);
                $total += $producto->precio * $cantidad;

                OrdenDetalle::create([
                    'orden_id' => $orden->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $producto->precio,
                ]);
            }

            $orden->update(['total' => $total]);
        });
    }
}