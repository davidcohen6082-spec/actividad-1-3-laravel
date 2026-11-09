<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Orden extends Model
{
    public const ESTADOS = ['pendiente', 'completada', 'cancelada'];

    protected $table = 'ordenes';
    protected $fillable = ['usuario_id', 'estado', 'total', 'fecha'];

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
