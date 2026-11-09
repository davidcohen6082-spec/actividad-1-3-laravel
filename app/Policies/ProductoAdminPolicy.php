<?php

namespace App\Policies;

use App\Models\Administrador;
use App\Models\Producto;

/**
 * Actividad 4.3 · Opción A (Policies). Regula el recurso administrativo (productos).
 * Se evalúa SIEMPRE sobre el administrador autenticado por el token y lee su nivel desde
 * la base de datos; nunca un valor enviado por el cliente.
 *
 *   Nivel 1: consulta (viewAny, view)
 *   Nivel 2: + crear y editar (create, update)
 *   Nivel 3: + eliminar, restaurar y eliminar definitivamente (delete, restore, forceDelete)
 *
 * Los métodos aceptan el modelo como opcional para poder usarse a nivel de clase
 * (por ejemplo desde un FormRequest, antes de buscar el registro).
 */
class ProductoAdminPolicy
{
    private function nivel(Administrador $admin): int
    {
        return $admin->activo ? (int) $admin->nivel : 0;
    }

    public function viewAny(Administrador $admin): bool
    {
        return $this->nivel($admin) >= 1;
    }

    public function view(Administrador $admin, ?Producto $producto = null): bool
    {
        return $this->nivel($admin) >= 1;
    }

    public function create(Administrador $admin): bool
    {
        return $this->nivel($admin) >= 2;
    }

    public function update(Administrador $admin, ?Producto $producto = null): bool
    {
        return $this->nivel($admin) >= 2;
    }

    public function delete(Administrador $admin, ?Producto $producto = null): bool
    {
        return $this->nivel($admin) >= 3;
    }

    public function restore(Administrador $admin, ?Producto $producto = null): bool
    {
        return $this->nivel($admin) >= 3;
    }

    public function forceDelete(Administrador $admin, ?Producto $producto = null): bool
    {
        return $this->nivel($admin) >= 3;
    }
}
