<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * Las categorías no tienen papelera: DELETE las elimina de forma definitiva,
 * siempre que no tengan productos asociados.
 */
class CategoriaController extends RecursoController
{
    protected function modelo(): string
    {
        return Categoria::class;
    }

    protected function etiqueta(): string
    {
        return 'categoría';
    }

    protected function conteos(): array
    {
        return ['productos'];
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
            'nombre' => ['required', 'string', 'min:3', 'max:100', Rule::unique('categorias', 'nombre')->ignore($registro?->id)],
        ];
    }

    protected function mensajes(): array
    {
        return [
            'nombre.required' => 'El nombre de la categoría es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'nombre.unique' => 'Ya existe una categoría con ese nombre.',
        ];
    }

    protected function motivoBloqueoEliminar(Model $registro, bool $definitivo): ?string
    {
        if (Producto::withTrashed()->where('categoria_id', $registro->id)->exists()) {
            return 'No se puede eliminar: la categoría tiene productos asociados.';
        }

        return null;
    }
}
