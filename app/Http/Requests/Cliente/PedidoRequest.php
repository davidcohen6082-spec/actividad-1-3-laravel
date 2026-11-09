<?php

namespace App\Http\Requests\Cliente;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Solo se aceptan producto y cantidad. El precio y el total NUNCA se leen de la
     * solicitud: el servidor los calcula con los precios guardados en la base de datos.
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.producto_id' => ['required', 'integer', 'distinct', Rule::exists('productos', 'id')->whereNull('deleted_at')],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Debes incluir al menos un producto en el pedido.',
            'items.min' => 'Debes incluir al menos un producto en el pedido.',
            'items.*.producto_id.required' => 'Cada artículo necesita un producto_id.',
            'items.*.producto_id.exists' => 'Uno de los productos no existe o ya no está disponible.',
            'items.*.producto_id.distinct' => 'No repitas el mismo producto: ajusta su cantidad.',
            'items.*.cantidad.required' => 'Cada artículo necesita una cantidad.',
            'items.*.cantidad.min' => 'La cantidad mínima es 1.',
        ];
    }
}
