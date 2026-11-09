<?php

namespace App\Http\Requests\Client;

use App\Models\Orden;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * POST / PUT / PATCH /api/v1/client/mis-registros.
 *
 * El propietario (usuario_id) NO es un campo de entrada: lo asigna el backend con el usuario
 * autenticado. Aunque el cliente lo envíe en el JSON, validated() no lo incluye y se ignora.
 *
 * Orden de evaluación: 401 (middleware) -> 404 (no existe) -> 403 (Policy) -> 422 (reglas).
 */
class ClientResourceRequest extends FormRequest
{
    private ?Orden $registroCache = null;

    private bool $registroBuscado = false;

    /** Registro que se edita (solo PUT/PATCH). */
    public function registro(): ?Orden
    {
        if (! $this->registroBuscado) {
            $id = $this->route('id');
            $this->registroCache = ctype_digit((string) $id) ? Orden::find($id) : null;
            $this->registroBuscado = true;
        }

        return $this->registroCache;
    }

    public function authorize(): bool
    {
        if ($this->isMethod('POST')) {
            return true; // cualquier cliente autenticado puede crear registros propios
        }

        $registro = $this->registro();
        abort_if($registro === null, 404, 'El registro solicitado no existe.');

        return $this->user()->can('update', $registro);
    }

    protected function failedAuthorization(): void
    {
        throw new AuthorizationException('No tienes autorización para modificar un registro que pertenece a otro cliente.');
    }

    public function rules(): array
    {
        if ($this->isMethod('POST')) {
            return [
                'items' => ['required', 'array', 'min:1', 'max:20'],
                'items.*.producto_id' => ['required', 'integer', 'distinct', Rule::exists('productos', 'id')->whereNull('deleted_at')],
                'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:100'],
                'notas' => ['nullable', 'string', 'max:255'],
            ];
        }

        return [
            // Los productos de un registro ya creado no se modifican: se cancela y se crea otro.
            'items' => ['prohibited'],
            'notas' => [$this->isMethod('PUT') ? 'present' : 'sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->isMethod('PATCH') && ! $this->has('notas')) {
                $validator->errors()->add('cuerpo', 'Envía al menos un campo para actualizar (notas).');
            }
        });
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Debes enviar al menos un producto en items.',
            'items.array' => 'items debe ser una lista de productos.',
            'items.min' => 'Debes enviar al menos un producto.',
            'items.max' => 'Un registro admite máximo 20 productos distintos.',
            'items.prohibited' => 'Los productos de un registro no se pueden modificar; cancélalo y crea uno nuevo.',
            'items.*.producto_id.required' => 'Indica el producto de cada renglón.',
            'items.*.producto_id.exists' => 'El producto indicado no existe o ya no está disponible.',
            'items.*.producto_id.distinct' => 'No repitas el mismo producto en items; aumenta su cantidad.',
            'items.*.cantidad.required' => 'Indica la cantidad de cada producto.',
            'items.*.cantidad.integer' => 'La cantidad debe ser un número entero.',
            'items.*.cantidad.min' => 'La cantidad debe ser al menos 1 (valores positivos).',
            'items.*.cantidad.max' => 'La cantidad máxima por producto es 100.',
            'notas.max' => 'Las notas no deben superar los 255 caracteres.',
            'notas.present' => 'En PUT debes enviar el campo notas (puede ir nulo).',
        ];
    }
}
