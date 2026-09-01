<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListaDeseo extends Model
{
    protected $fillable = ['usuario_id', 'producto_id', 'fecha_agregado'];

public function usuario() { return $this->belongsTo(Usuario::class); }
public function producto() { return $this->belongsTo(Producto::class); }
}
