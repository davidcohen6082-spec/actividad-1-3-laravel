<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // endpoint público: aquí solo se validan los datos de entrada
    }

    public function rules(): array
    {
        return [
            'usuario' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'usuario.required' => 'El usuario es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
        ];
    }
}
