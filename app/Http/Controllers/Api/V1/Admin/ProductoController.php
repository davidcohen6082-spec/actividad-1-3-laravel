<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductoController extends RecursoController
{
    protected function modelo(): string
    {
        return Producto::class;
    }

    protected function etiqueta(): string
    {
        return 'producto';
    }

    protected function relaciones(): array
    {
        return ['categoria', 'usuario'];
    }

    protected function buscables(): array
    {
        return ['nombre', 'descripcion'];
    }

    protected function filtros(): array
    {
        return ['categoria_id', 'condicion'];
    }

    protected function ordenables(): array
    {
        return ['id', 'nombre', 'precio', 'stock'];
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'nombre' => ['required', 'string', 'min:3', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'categoria_id' => ['required', Rule::exists('categorias', 'id')],
            'condicion' => ['required', 'in:nuevo,usado'],
            'talla' => ['nullable', 'string', 'max:20'],
            'precio' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'stock' => ['required', 'integer', 'min:0', 'max:1000'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    protected function mensajes(): array
    {
        return [
            'nombre.required' => 'El nombre del artículo es obligatorio.',
            'categoria_id.required' => 'Selecciona una categoría.',
            'categoria_id.exists' => 'La categoría seleccionada no es válida.',
            'condicion.required' => 'Selecciona la condición del artículo.',
            'condicion.in' => 'La condición debe ser "nuevo" o "usado".',
            'precio.required' => 'El precio es obligatorio.',
            'precio.numeric' => 'El precio debe ser un número.',
            'precio.min' => 'El precio no puede ser negativo.',
            'stock.required' => 'El stock es obligatorio.',
            'stock.integer' => 'El stock debe ser un número entero.',
            'imagen.mimes' => 'La imagen debe ser jpg, jpeg, png o webp.',
            'imagen.max' => 'La imagen no debe superar los 2 MB.',
        ];
    }

    protected function atributos(array $validados, ?Model $registro): array
    {
        unset($validados['imagen']);

        if ($registro === null) {
            // El producto queda a nombre de un usuario del sistema (mismo criterio que la parte web).
            $validados['usuario_id'] = Usuario::whereHas('rol', fn ($q) => $q->where('nombre', 'administrador'))->value('id')
                ?? Usuario::value('id');
        }

        return $validados;
    }

    protected function despuesDeGuardar(Request $request, Model $registro): void
    {
        if (! $request->hasFile('imagen')) {
            return;
        }

        if ($registro->imagen && file_exists(public_path($registro->imagen))) {
            unlink(public_path($registro->imagen));
        }

        $extension = $request->file('imagen')->getClientOriginalExtension();
        $nombreArchivo = "Producto_{$registro->id}_1.{$extension}";
        $request->file('imagen')->move(public_path('images/productos'), $nombreArchivo);

        $registro->imagen = 'images/productos/'.$nombreArchivo;
        $registro->save();
    }

    protected function motivoBloqueoEliminar(Model $registro, bool $definitivo): ?string
    {
        if ($definitivo
            && ($registro->carritoItems()->exists() || $registro->listaDeseos()->exists() || $registro->ordenDetalles()->exists())) {
            return 'No se puede eliminar definitivamente: el producto está referenciado en carritos, listas de deseos u órdenes.';
        }

        return null;
    }

    protected function alEliminarDefinitivamente(Model $registro): void
    {
        if ($registro->imagen && file_exists(public_path($registro->imagen))) {
            unlink(public_path($registro->imagen));
        }
    }
}
