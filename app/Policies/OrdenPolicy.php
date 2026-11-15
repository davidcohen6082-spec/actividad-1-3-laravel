<?php

namespace App\Policies;

use App\Models\Orden;
use App\Models\Usuario;
use Illuminate\Auth\Access\Response;

class OrdenPolicy
{
    /** Consultar: solo el propietario. */
    public function view(Usuario $usuario, Orden $orden): bool
    {
        return $orden->usuario_id === $usuario->id;
    }

    /** Modificar: solo el propietario. */
    public function update(Usuario $usuario, Orden $orden): bool
    {
        return $orden->usuario_id === $usuario->id;
    }

    /** Cancelar (compatibilidad con la 4.1): solo el propietario. */
    public function cancel(Usuario $usuario, Orden $orden): bool
    {
        return $orden->usuario_id === $usuario->id;
    }

    /** Eliminar/cancelar: propietario Y registro en estado cancelable (pendiente). */
    public function delete(Usuario $usuario, Orden $orden): Response
    {
        if ($orden->usuario_id !== $usuario->id) {
            return Response::deny('No tienes autorización para cancelar un registro que pertenece a otro cliente.');
        }

        return $orden->esCancelable()
            ? Response::allow()
            : Response::deny("Este registro ya no es cancelable (estado actual: {$orden->estado}).");
    }
}
