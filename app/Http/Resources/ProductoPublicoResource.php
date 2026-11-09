<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Vista pública de un producto: solo los campos necesarios para el catálogo
 * (no se expone el usuario que lo publicó ni datos internos).
 */
class ProductoPublicoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'talla' => $this->talla,
            'condicion' => $this->condicion,
            'precio' => (float) $this->precio,
            'stock' => $this->stock,
            'imagen' => $this->imagen,
            'categoria' => $this->whenLoaded('categoria', fn () => [
                'id' => $this->categoria->id,
                'nombre' => $this->categoria->nombre,
            ]),
        ];
    }
}
