<?php

namespace App\Http\Controllers\Api\V1\Cliente;

use App\Http\Controllers\Api\V1\Concerns\RespuestasApi;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PerfilController extends Controller
{
    use RespuestasApi;

    public function show(Request $request)
    {
        return $this->ok($request->user()->load('rol'));
    }

    /**
     * Actualización parcial del perfil. Solo se pueden cambiar los campos listados aquí:
     * el rol, la fecha de registro u otros campos internos se ignoran aunque se envíen.
     */
    public function update(Request $request)
    {
        $cliente = $request->user();

        $datos = $request->validate([
            'nombre' => ['sometimes', 'required', 'string', 'min:3', 'max:100'],
            'email' => ['sometimes', 'required', 'email', 'max:150', Rule::unique('usuarios', 'email')->ignore($cliente->id)],
            'password' => ['sometimes', 'required', 'string', 'min:8', 'max:100', 'confirmed'],
            'password_actual' => ['required_with:password', 'string'],
        ], [
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.unique' => 'Ese correo ya está registrado.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide (campo password_confirmation).',
            'password_actual.required_with' => 'Para cambiar la contraseña debes enviar password_actual.',
        ]);

        if (isset($datos['password'])) {
            if (! Hash::check($datos['password_actual'], $cliente->password_hash)) {
                throw ValidationException::withMessages(['password_actual' => ['La contraseña actual no es correcta.']]);
            }

            $datos['password_hash'] = Hash::make($datos['password']);
        }

        unset($datos['password'], $datos['password_actual']);

        $cliente->fill($datos)->save();

        return $this->ok($cliente->load('rol'), 'Perfil actualizado correctamente.');
    }
}
