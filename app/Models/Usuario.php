<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $fillable = ['rol_id', 'nombre', 'email', 'password_hash', 'proveedor_social', 'social_id', 'fecha_registro'];

public function rol() { return $this->belongsTo(Rol::class); }
public function productos() { return $this->hasMany(Producto::class); }
public function carrito() { return $this->hasOne(Carrito::class); }
public function listaDeseos() { return $this->hasMany(ListaDeseo::class); }
public function ordenes() { return $this->hasMany(Orden::class); }
public function logs() { return $this->hasMany(Log::class); }
}
