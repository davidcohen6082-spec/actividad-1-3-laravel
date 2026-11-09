<?php

namespace Database\Seeders;

use App\Models\Orden;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Cuentas de cliente de PRUEBA para demostrar la API (no son credenciales reales).
 *   cliente1@example.com / cliente123   (tiene un pedido pendiente)
 *   cliente2@example.com / cliente123   (tiene un pedido completado)
 * Sirven para comprobar que un cliente no puede ver los pedidos de otro.
 */
class ClienteSeeder extends Seeder
{
    public function run(): void
    {
        $rolCliente = Rol::firstOrCreate(['nombre' => 'cliente']);
        $productos = Producto::orderBy('id')->take(2)->get();

        if ($productos->count() < 2) {
            return;
        }

        $productos->each(fn ($p) => $p->update(['stock' => max($p->stock, 10)]));

        $cuentas = [
            ['Ana Cliente', 'cliente1@example.com', 'pendiente', $productos[0]],
            ['Luis Cliente', 'cliente2@example.com', 'completada', $productos[1]],
        ];

        foreach ($cuentas as [$nombre, $email, $estado, $producto]) {
            $cliente = Usuario::updateOrCreate(
                ['email' => $email],
                [
                    'rol_id' => $rolCliente->id,
                    'nombre' => $nombre,
                    'password_hash' => Hash::make('cliente123'),
                    'fecha_registro' => now(),
                ]
            );

            if ($cliente->ordenes()->exists()) {
                continue;
            }

            $pedido = Orden::create([
                'usuario_id' => $cliente->id,
                'estado' => $estado,
                'total' => $producto->precio * 2,
                'fecha' => now(),
            ]);

            $pedido->detalles()->create([
                'producto_id' => $producto->id,
                'cantidad' => 2,
                'precio_unitario' => $producto->precio,
            ]);
        }
    }
}
