<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespuestasApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductoPublicoResource;
use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Http\Request;

/**
 * Catálogo público: no requiere iniciar sesión y es de solo lectura.
 */
class CatalogoController extends Controller
{
    use RespuestasApi;

    public function productos(Request $request)
    {
        $request->validate([
            'categoria_id' => ['nullable', 'integer'],
            'condicion' => ['nullable', 'in:nuevo,usado'],
            'precio_min' => ['nullable', 'numeric', 'min:0'],
            'precio_max' => ['nullable', 'numeric', 'min:0'],
            'buscar' => ['nullable', 'string', 'max:100'],
            'ordenar' => ['nullable', 'in:id,-id,nombre,-nombre,precio,-precio'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $ordenar = (string) $request->query('ordenar', 'id');

        $productos = Producto::with('categoria:id,nombre')
            ->when($request->filled('categoria_id'), fn ($q) => $q->where('categoria_id', $request->query('categoria_id')))
            ->when($request->filled('condicion'), fn ($q) => $q->where('condicion', $request->query('condicion')))
            ->when($request->filled('precio_min'), fn ($q) => $q->where('precio', '>=', $request->query('precio_min')))
            ->when($request->filled('precio_max'), fn ($q) => $q->where('precio', '<=', $request->query('precio_max')))
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $buscar = $request->query('buscar');
                $q->where(fn ($w) => $w->where('nombre', 'like', "%{$buscar}%")->orWhere('descripcion', 'like', "%{$buscar}%"));
            })
            ->orderBy(ltrim($ordenar, '-'), str_starts_with($ordenar, '-') ? 'desc' : 'asc')
            ->paginate($this->porPagina($request))
            ->withQueryString();

        return $this->paginado(
            $productos->through(fn ($producto) => (new ProductoPublicoResource($producto))->resolve())
        );
    }

    public function producto($id)
    {
        $producto = Producto::with('categoria:id,nombre')->find($id);

        if (! $producto) {
            return $this->error('El producto solicitado no existe.', 404);
        }

        return $this->ok(new ProductoPublicoResource($producto));
    }

    public function categorias()
    {
        return $this->ok(Categoria::orderBy('nombre')->get(['id', 'nombre']));
    }
}
