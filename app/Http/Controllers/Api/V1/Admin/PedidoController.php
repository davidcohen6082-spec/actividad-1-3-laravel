<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Concerns\RespuestasApi;
use App\Http\Controllers\Controller;
use App\Models\Orden;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Gestión administrativa de pedidos: consultar todos y cambiar su estado.
 * Los pedidos no se crean ni se borran desde aquí (los crea el cliente).
 */
class PedidoController extends Controller
{
    use RespuestasApi;

    public function index(Request $request)
    {
        $this->permitir($request, 'ver-registros');

        $request->validate([
            'estado' => ['nullable', Rule::in(Orden::ESTADOS)],
            'usuario_id' => ['nullable', 'integer'],
        ]);

        $pedidos = Orden::with(['usuario:id,nombre,email', 'detalles.producto:id,nombre'])
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->query('estado')))
            ->when($request->filled('usuario_id'), fn ($q) => $q->where('usuario_id', $request->query('usuario_id')))
            ->orderByDesc('fecha')
            ->paginate($this->porPagina($request));

        return $this->paginado($pedidos->withQueryString());
    }

    public function show(Request $request, $id)
    {
        $this->permitir($request, 'ver-registros');

        $pedido = Orden::with(['usuario:id,nombre,email', 'detalles.producto:id,nombre'])->find($id);

        return $pedido ? $this->ok($pedido) : $this->error('El pedido solicitado no existe.', 404);
    }

    public function update(Request $request, $id)
    {
        $this->permitir($request, 'gestionar-pedidos', 'No tienes autorización para modificar pedidos.');

        $pedido = Orden::with('detalles')->find($id);

        if (! $pedido) {
            return $this->error('El pedido solicitado no existe.', 404);
        }

        $datos = $request->validate([
            'estado' => ['required', Rule::in(Orden::ESTADOS)],
        ], [
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe ser: '.implode(', ', Orden::ESTADOS).'.',
        ]);

        if ($pedido->estado !== 'pendiente') {
            return $this->error("Solo se puede cambiar el estado de un pedido pendiente (estado actual: {$pedido->estado}).", 409);
        }

        if ($datos['estado'] === 'cancelada') {
            $pedido->cancelar();
        } elseif ($datos['estado'] === 'completada') {
            $pedido->update(['estado' => 'completada']);
        }

        return $this->ok($pedido->fresh(['usuario:id,nombre,email', 'detalles.producto:id,nombre']), 'Estado del pedido actualizado correctamente.');
    }
}
