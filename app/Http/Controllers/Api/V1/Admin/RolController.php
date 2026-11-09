<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class RolController extends RecursoController
{
    protected function modelo(): string
    {
        return Rol::class;
    }

    protected function etiqueta(): string
    {
        return 'rol';
    }

    protected function conteos(): array
    {
        return ['usuarios'];
    }

    protected function buscables(): array
    {
        return ['nombre'];
    }

    protected function ordenables(): array
    {
        return ['id', 'nombre'];
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'nombre' => ['required', 'string', 'min:3', 'max:50', Rule::unique('roles', 'nombre')->ignore($registro?->id)],
        ];
    }

    protected function mensajes(): array
    {
        return [
            'nombre.required' => 'El nombre del rol es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'nombre.max' => 'El nombre no puede superar los 50 caracteres.',
            'nombre.unique' => 'Ya existe un rol con ese nombre.',
        ];
    }

    protected function motivoBloqueoEliminar(Model $registro, bool $definitivo): ?string
    {
        if ($definitivo && Usuario::withTrashed()->where('rol_id', $registro->id)->exists()) {
            return 'No se puede eliminar definitivamente: hay usuarios asociados a este rol.';
        }

        return null;
    }
}
