<?php

namespace App\Http\Requests\Admin;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validación de PATCH /api/v1/admin/usuarios/{id}/estado.
 * Como clientes y administradores viven en tablas distintas, "tipo" indica a cuál se refiere el {id}.
 */
class CambiarEstadoUsuarioRequest extends FormRequest
{
    /** Opción B (Gates): solo el nivel 3 gestiona cuentas de otros usuarios. */
    public function authorize(): bool
    {
        return $this->user()->can('gestionar-usuarios');
    }

    protected function failedAuthorization(): void
    {
        throw new AuthorizationException('Solo un administrador de nivel 3 puede cambiar el estado o rol de los usuarios.');
    }

    public function tipo(): string
    {
        return $this->input('tipo', 'cliente');
    }

    public function rules(): array
    {
        return [
            'tipo' => ['sometimes', Rule::in(['cliente', 'administrador'])],
            'activo' => ['sometimes', 'boolean'],
            'rol_id' => ['sometimes', 'integer', Rule::exists('roles', 'id')->whereNull('deleted_at')],
            'nivel' => ['sometimes', 'integer', 'between:1,3'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->hasAny(['activo', 'rol_id', 'nivel'])) {
                $validator->errors()->add('cuerpo', 'Envía al menos uno de estos campos: activo, rol_id (clientes) o nivel (administradores).');
            }

            if ($this->tipo() === 'cliente' && $this->has('nivel')) {
                $validator->errors()->add('nivel', 'El nivel solo aplica a administradores (tipo = administrador).');
            }

            if ($this->tipo() === 'administrador' && $this->has('rol_id')) {
                $validator->errors()->add('rol_id', 'El rol solo aplica a clientes (tipo = cliente).');
            }
        });
    }

    public function messages(): array
    {
        return [
            'tipo.in' => 'El tipo debe ser "cliente" o "administrador".',
            'activo.boolean' => 'El campo activo debe ser verdadero o falso.',
            'rol_id.integer' => 'El rol debe ser un identificador numérico.',
            'rol_id.exists' => 'El rol seleccionado no existe.',
            'nivel.integer' => 'El nivel debe ser un número entero.',
            'nivel.between' => 'El nivel debe estar entre 1 y 3.',
        ];
    }
}
