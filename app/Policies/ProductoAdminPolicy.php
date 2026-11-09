<?php

namespace App\Policies;

use App\Models\Administrador;
use App\Models\Producto;


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
