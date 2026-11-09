<?php

namespace App\Http\Resources\Admin;

use App\Models\Administrador;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Vista unificada de clientes (tabla usuarios) y administradores (tabla administradores).
 * Nunca expone contraseñas, hashes, tokens ni identificadores de redes sociales.
 */
class UsuarioAdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $esAdmin = $this->resource instanceof Administrador;
        $activo = (bool) ($this->activo ?? true);

        return [
            'id' => $this->id,
            'tipo' => $esAdmin ? 'administrador' : 'cliente',
            'nombre' => $esAdmin ? $this->nombre_completo : $this->nombre,
            'identificador' => $esAdmin ? $this->usuario : $this->email,
            'rol' => $esAdmin ? 'administrador' : $this->rol?->nombre,
            'rol_id' => $esAdmin ? null : $this->rol_id,
            'nivel' => $esAdmin ? (int) $this->nivel : null,
            'activo' => $activo,
            'estado' => $activo ? 'activo' : 'inactivo',
            'registrado_en' => $this->created_at?->toIso8601String(),
        ];
    }
}
