<?php

namespace App\Policies;

use App\Models\Orden;
use App\Models\Usuario;

class OrdenPolicy
{
    /** Un cliente solo puede ver sus propios pedidos. */
    public function view(Usuario $usuario, Orden $orden): bool
    {
        return $orden->usuario_id === $usuario->id;
    }

    /** Un cliente solo puede cancelar sus propios pedidos. */
    public function cancel(Usuario $usuario, Orden $orden): bool
    {
        return $orden->usuario_id === $usuario->id;
    }
}
