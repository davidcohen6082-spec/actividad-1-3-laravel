<?php

namespace App\Http\Controllers\Api\V1\Cliente;

use App\Http\Controllers\Api\V1\Concerns\RespuestasApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cliente\PedidoRequest;
use App\Models\Orden;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PedidoController extends Controller
{
    use RespuestasApi;

    /** Solo los pedidos del cliente autenticado. */
    public function index(Request $request)
    {
        $request->validate(['estado' => ['nullable', Rule::in(Orden::ESTADOS)]]);

        $pedidos = $request->user()->ordenes()
            ->with('detalles.producto:id,nombre')
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->query('estado')))
            ->orderByDesc('fecha')
            ->paginate($this->porPagina($request));

        return $this->paginado($pedidos->withQueryString());
    }

    public function store(PedidoRequest $request)
    {
        $cliente = $request->user();
        $articulos = collect($request->validated('items'))->values();

        $pedido = DB::transaction(function () use ($cliente, $articulos) {
            $productos = Producto::whereIn('id', $articulos->pluck('producto_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $errores = [];
            foreach ($articulos as $i => $articulo) {
                $producto = $productos[$articulo['producto_id']];

                if ($producto->stock < $articulo['cantidad']) {
                    $errores["items.{$i}.cantidad"] = ["Stock insuficiente para \"{$producto->nombre}\" (disponible: {$producto->stock})."];
                }
            }

            if ($errores) {
                throw ValidationException::withMessages($errores);
            }

            $pedido = Orden::create([
                'usuario_id' => $cliente->id,
                'estado' => 'pendiente',
                'total' => 0,
                'fecha' => now(),
            ]);

            $total = 0;
            foreach ($articulos as $articulo) {
                $producto = $productos[$articulo['producto_id']];

                // El precio sale de la base de datos, nunca de la solicitud.
                $pedido->detalles()->create([
                    'producto_id' => $producto->id,
                    'cantidad' => $articulo['cantidad'],
                    'precio_unitario' => $producto->precio,
                ]);

                $producto->decrement('stock', $articulo['cantidad']);
                $total += $producto->precio * $articulo['cantidad'];
            }

            $pedido->update(['total' => round($total, 2)]);

            return $pedido;
        });

        return $this->creado($pedido->load('detalles.producto:id,nombre'), 'Pedido registrado correctamente.');
    }

    public function show(Request $request, $id)
    {
        $pedido = Orden::with('detalles.producto:id,nombre')->find($id);

        if (! $pedido) {
            return $this->error('El pedido solicitado no existe.', 404);
        }

        // Policy: el identificador de la URL no demuestra propiedad; se comprueba en el servidor.
        $this->permitirSobre($request, 'view', $pedido, 'No tienes autorización para consultar este pedido.');

        return $this->ok($pedido);
    }

    /** Actualización parcial: el único cambio permitido a un cliente es cancelar su pedido pendiente. */
    public function update(Request $request, $id)
    {
        $pedido = Orden::with('detalles')->find($id);

        if (! $pedido) {
            return $this->error('El pedido solicitado no existe.', 404);
        }

        $this->permitirSobre($request, 'cancel', $pedido, 'No tienes autorización para modificar este pedido.');

        $request->validate(
            ['estado' => ['required', 'in:cancelada']],
            ['estado.required' => 'El estado es obligatorio.', 'estado.in' => 'Un cliente solo puede cambiar el estado a "cancelada".']
        );

        if ($pedido->estado !== 'pendiente') {
            return $this->error("Solo se puede cancelar un pedido pendiente (estado actual: {$pedido->estado}).", 409);
        }

        $pedido->cancelar();

        return $this->ok($pedido->fresh('detalles.producto:id,nombre'), 'Pedido cancelado correctamente.');
    }
}
