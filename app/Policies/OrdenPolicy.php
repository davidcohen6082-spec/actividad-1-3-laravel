<?php

namespace App\Policies;

use App\Models\Orden;
use App\Models\Usuario;
use Illuminate\Auth\Access\Response;

/**
 * Actividad 4.4 · Autorización de DATOS PROPIOS (Self-Data Ownership).
 *
 * Todas las reglas comparan la identidad del usuario autenticado por Sanctum ($usuario->id,
 * leída del token en el servidor) con el propietario del registro ($orden->usuario_id).
 * En este proyecto la columna propietaria se llama usuario_id (equivale a user_id).
 * El identificador que llega en la URL NO prueba propiedad: siempre se comprueba aquí.
 */
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
