<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * PUT / PATCH /api/v1/client/perfil. El perfil siempre es el del usuario autenticado
 * (no hay id en la URL). Rol, fecha de registro y demás campos internos se ignoran.
 */
class ClientProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $reglas = [
            'nombre' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('usuarios', 'email')->ignore($this->user()->id)],
            'password' => ['sometimes', 'required', 'string', 'min:8', 'max:100', 'confirmed'],
            'password_actual' => ['required_with:password', 'string'],
        ];

        // PATCH: parcial. Un campo presente con null o vacío sigue fallando "required".
        if ($this->isMethod('PATCH')) {
            foreach (['nombre', 'email'] as $campo) {
                array_unshift($reglas[$campo], 'sometimes');
            }
        }

        return $reglas;
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.unique' => 'Ese correo ya está registrado.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide (campo password_confirmation).',
            'password_actual.required_with' => 'Para cambiar la contraseña debes enviar password_actual.',
        ];
    }
}
