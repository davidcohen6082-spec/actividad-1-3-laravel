<?php

namespace App\Policies;

use App\Models\ListaDeseo;
use App\Models\Usuario;

class ListaDeseoPolicy
{
    public function delete(Usuario $usuario, ListaDeseo $deseo): bool
    {
        return $deseo->usuario_id === $usuario->id;
    }
}
