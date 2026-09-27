<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'categoria_id', 'usuario_id', 'nombre', 'descripcion',
        'talla', 'condicion', 'precio', 'stock', 'imagen',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }

    public function carritoItems()
    {
        return $this->hasMany(CarritoItem::class);
    }

    public function listaDeseos()
    {
        return $this->hasMany(ListaDeseo::class);
    }

    public function ordenDetalles()
    {
        return $this->hasMany(OrdenDetalle::class);
    }
}