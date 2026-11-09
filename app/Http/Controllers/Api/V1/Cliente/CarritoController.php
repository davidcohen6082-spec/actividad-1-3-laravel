<?php

namespace App\Http\Controllers\Api\V1\Cliente;

use App\Http\Controllers\Api\V1\Concerns\RespuestasApi;
use App\Http\Controllers\Controller;
use App\Models\Carrito;
use App\Models\CarritoItem;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CarritoController extends Controller
{
    use RespuestasApi;

    public function show(Request $request)
    {
        return $this->ok($this->contenido($this->carritoDe($request)));
    }

    public function agregar(Request $request)
    {
        $datos = $request->validate([
            'producto_id' => ['required', 'integer', Rule::exists('productos', 'id')->whereNull('deleted_at')],
            'cantidad' => ['required', 'integer', 'min:1', 'max:100'],
        ], [
            'producto_id.required' => 'El producto_id es obligatorio.',
            'producto_id.exists' => 'El producto no existe o ya no está disponible.',
            'cantidad.required' => 'La cantidad es obligatoria.',
            'cantidad.min' => 'La cantidad mínima es 1.',
        ]);

        $carrito = $this->carritoDe($request);
        $item = $carrito->items()->where('producto_id', $datos['producto_id'])->first();
        $cantidad = $datos['cantidad'] + ($item?->cantidad ?? 0);

        $this->verificarStock($datos['producto_id'], $cantidad);

        if ($item) {
            $item->update(['cantidad' => $cantidad]);

            return $this->ok($this->contenido($carrito), 'Cantidad actualizada en el carrito.');
        }

        $carrito->items()->create(['producto_id' => $datos['producto_id'], 'cantidad' => $cantidad]);

        return $this->creado($this->contenido($carrito), 'Producto agregado al carrito.');
    }

    public function actualizar(Request $request, $id)
    {
        $item = CarritoItem::with('carrito')->find($id);

        if (! $item) {
            return $this->error('El artículo del carrito no existe.', 404);
        }

        $this->permitirSobre($request, 'update', $item, 'No tienes autorización para modificar este artículo.');

        $datos = $request->validate(
            ['cantidad' => ['required', 'integer', 'min:1', 'max:100']],
            ['cantidad.required' => 'La cantidad es obligatoria.', 'cantidad.min' => 'La cantidad mínima es 1.']
        );

        $this->verificarStock($item->producto_id, $datos['cantidad']);
        $item->update(['cantidad' => $datos['cantidad']]);

        return $this->ok($this->contenido($item->carrito), 'Cantidad actualizada.');
    }

    public function quitar(Request $request, $id)
    {
        $item = CarritoItem::with('carrito')->find($id);

        if (! $item) {
            return $this->error('El artículo del carrito no existe.', 404);
        }

        $this->permitirSobre($request, 'delete', $item, 'No tienes autorización para eliminar este artículo.');

        $item->delete();

        return $this->sinContenido();
    }

    private function carritoDe(Request $request): Carrito
    {
        return Carrito::firstOrCreate(['usuario_id' => $request->user()->id]);
    }

    private function verificarStock(int|string $productoId, int $cantidad): void
    {
        $producto = Producto::find($productoId);

        if ($producto && $producto->stock < $cantidad) {
            throw ValidationException::withMessages([
                'cantidad' => ["Stock insuficiente para \"{$producto->nombre}\" (disponible: {$producto->stock})."],
            ]);
        }
    }

    private function contenido(Carrito $carrito): array
    {
        $items = $carrito->items()->with('producto:id,nombre,precio,stock')->get()->filter(fn ($i) => $i->producto);

        return [
            'id' => $carrito->id,
            'items' => $items->values(),
            'total' => round($items->sum(fn ($i) => $i->cantidad * $i->producto->precio), 2),
        ];
    }
}
