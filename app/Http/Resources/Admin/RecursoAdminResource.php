<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación pública del recurso administrativo (tabla "productos").
 * Desacopla la API de la base de datos: nombres propios, tipos correctos y sin claves foráneas
 * internas (categoria_id, usuario_id) ni columnas técnicas (deleted_at).
 */
class RecursoAdminResource extends JsonResource
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
            'stock' => (int) $this->stock,
            'imagen_url' => $this->imagen ? url($this->imagen) : null,
            'activo' => $this->deleted_at === null,
            'estado' => $this->deleted_at === null ? 'activo' : 'inactivo',
            'categoria' => $this->whenLoaded('categoria', fn () => $this->categoria
                ? ['id' => $this->categoria->id, 'nombre' => $this->categoria->nombre]
                : null),
            'publicado_por' => $this->whenLoaded('usuario', fn () => $this->usuario?->nombre),
            'creado_en' => $this->created_at?->toIso8601String(),
            'actualizado_en' => $this->updated_at?->toIso8601String(),
            'desactivado_en' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
