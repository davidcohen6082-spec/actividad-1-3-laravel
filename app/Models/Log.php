<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Log extends Model
{
    use SoftDeletes;

    protected $fillable = ['usuario_id', 'accion', 'fecha', 'critico'];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }
}