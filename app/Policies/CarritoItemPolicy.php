<?php

namespace App\Policies;

use App\Models\CarritoItem;
use App\Models\Usuario;

class CarritoItemPolicy
{
    public function update(Usuario $usuario, CarritoItem $item): bool
    {
        return $item->carrito?->usuario_id === $usuario->id;
    }

    public function delete(Usuario $usuario, CarritoItem $item): bool
    {
        return $item->carrito?->usuario_id === $usuario->id;
    }
}
