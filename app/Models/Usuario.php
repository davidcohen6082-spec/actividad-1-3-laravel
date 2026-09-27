<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Usuario extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'rol_id', 'nombre', 'email', 'password_hash',
        'proveedor_social', 'social_id', 'fecha_registro', 'foto',
    ];

    public function rol()
    {
        return $this->belongsTo(Rol::class);
    }

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }

    public function carrito()
    {
        return $this->hasOne(Carrito::class);
    }

    public function listaDeseos()
    {
        return $this->hasMany(ListaDeseo::class);
    }

    public function ordenes()
    {
        return $this->hasMany(Orden::class);
    }

    public function logs()
    {
        return $this->hasMany(Log::class);
    }
}