<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Perfil del cliente. Whitelist explícita: password_hash, social_id, proveedor_social,
 * rol_id, deleted_at y demás columnas internas nunca salen en la API.
 */
class ClienteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'email' => $this->email,
            'rol' => $this->whenLoaded('rol', fn () => $this->rol?->nombre),
            'foto_url' => $this->foto ? url($this->foto) : null,
            'registrado_en' => $this->fecha_registro ? \Illuminate\Support\Carbon::parse($this->fecha_registro)->toIso8601String() : null,
        ];
    }
}
