<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Registro (pedido) del cliente. No expone usuario_id ni columnas técnicas. */
class RegistroClienteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'estado' => $this->estado,
            'cancelable' => $this->esCancelable(),
            'total' => (float) $this->total,
            'notas' => $this->notas,
            'fecha' => $this->fecha ? \Illuminate\Support\Carbon::parse($this->fecha)->toIso8601String() : null,
            'items' => $this->whenLoaded('detalles', fn () => $this->detalles->map(fn ($d) => [
                'producto' => ['id' => $d->producto_id, 'nombre' => $d->producto?->nombre],
                'cantidad' => (int) $d->cantidad,
                'precio_unitario' => (float) $d->precio_unitario,
                'subtotal' => round($d->cantidad * $d->precio_unitario, 2),
            ])->values()),
        ];
    }
}
