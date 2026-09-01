<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenDetalle extends Model
{
    protected $fillable = ['orden_id', 'producto_id', 'cantidad', 'precio_unitario'];

public function orden() { return $this->belongsTo(Orden::class); }
public function producto() { return $this->belongsTo(Producto::class); }
}
