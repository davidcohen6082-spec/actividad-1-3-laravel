<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Orden extends Model
{
    public const ESTADOS = ['pendiente', 'completada', 'cancelada'];

    protected $table = 'ordenes';
    protected $fillable = ['usuario_id', 'estado', 'total', 'notas', 'fecha'];

    /**
     * ELOQUENT SCOPE (Actividad 4.4): limita la consulta a los registros del propietario.
     * Toda lista que se devuelve a un cliente pasa por aquí, de modo que el filtro ocurre
     * en la base de datos y los registros ajenos nunca llegan a la respuesta.
     */
    public function scopePropios(Builder $consulta, int $usuarioId): Builder
    {
        return $consulta->where('usuario_id', $usuarioId);
    }

    public function esCancelable(): bool
    {
        return $this->estado === 'pendiente';
    }

    /**
     * Crea un registro (pedido) a nombre del usuario indicado. El propietario lo decide el
     * servidor (usuario autenticado) y los precios salen de la base de datos, nunca del cliente.
     *
     * @param  Collection<int, array{producto_id:int, cantidad:int}>  $articulos
     */
    public static function registrarPara(Usuario $cliente, Collection $articulos, ?string $notas = null): self
    {
        return DB::transaction(function () use ($cliente, $articulos, $notas) {
            $productos = Producto::whereIn('id', $articulos->pluck('producto_id'))->lockForUpdate()->get()->keyBy('id');

            $errores = [];
            foreach ($articulos as $i => $articulo) {
                $producto = $productos[$articulo['producto_id']];

                if ($producto->stock < $articulo['cantidad']) {
                    $errores["items.{$i}.cantidad"] = ["Stock insuficiente para \"{$producto->nombre}\" (disponible: {$producto->stock})."];
                }
            }

            if ($errores) {
                throw ValidationException::withMessages($errores);
            }

            $orden = self::create([
                'usuario_id' => $cliente->id,
                'estado' => 'pendiente',
                'total' => 0,
                'notas' => $notas,
                'fecha' => now(),
            ]);

            $total = 0;
            foreach ($articulos as $articulo) {
                $producto = $productos[$articulo['producto_id']];

                $orden->detalles()->create([
                    'producto_id' => $producto->id,
                    'cantidad' => $articulo['cantidad'],
                    'precio_unitario' => $producto->precio,
                ]);

                $producto->decrement('stock', $articulo['cantidad']);
                $total += $producto->precio * $articulo['cantidad'];
            }

            $orden->update(['total' => round($total, 2)]);

            return $orden;
        });
    }

    public function usuario() { return $this->belongsTo(Usuario::class); }
    public function detalles() { return $this->hasMany(OrdenDetalle::class); }
    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'orden_detalles')->withPivot('cantidad', 'precio_unitario')->withTimestamps();
    }

    /**
     * Cancela un pedido pendiente y devuelve el stock de sus productos.
     * Lo usan tanto el cliente (su propio pedido) como el administrador.
     */
    public function cancelar(): void
    {
        DB::transaction(function () {
            foreach ($this->detalles as $detalle) {
                Producto::withTrashed()->whereKey($detalle->producto_id)->increment('stock', $detalle->cantidad);
            }

            $this->update(['estado' => 'cancelada']);
        });
    }
}
