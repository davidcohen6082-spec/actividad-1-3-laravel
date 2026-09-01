<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Orden extends Model
{
    protected $table = 'ordenes';
    protected $fillable = ['usuario_id', 'estado', 'total', 'fecha'];

public function usuario() { return $this->belongsTo(Usuario::class); }
public function detalles() { return $this->hasMany(OrdenDetalle::class); }
public function productos()
{
    return $this->belongsToMany(Producto::class, 'orden_detalles')->withPivot('cantidad', 'precio_unitario')->withTimestamps();
}
}
