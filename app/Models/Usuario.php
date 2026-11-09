<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, SoftDeletes;

    protected $fillable = [
        'rol_id', 'nombre', 'email', 'password_hash',
        'proveedor_social', 'social_id', 'fecha_registro', 'foto',
    ];

    // Nunca se exponen en las respuestas JSON.
    protected $hidden = ['password_hash', 'social_id'];

    /**
     * La contraseña de este modelo vive en la columna password_hash.
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function esCliente(): bool
    {
        return $this->rol?->nombre === 'cliente';
    }

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
