<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarritoItem extends Model
{
    protected $fillable = ['carrito_id', 'producto_id', 'cantidad'];

public function carrito() { return $this->belongsTo(Carrito::class); }
public function producto() { return $this->belongsTo(Producto::class); }
}
