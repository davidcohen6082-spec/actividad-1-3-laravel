<?php

namespace App\Http\Controllers\Api\V1\Cliente;

use App\Http\Controllers\Api\V1\Concerns\RespuestasApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cliente\RegistroRequest;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use RespuestasApi;

    /** Autorregistro: el rol siempre es "cliente"; nunca se toma de la solicitud. */
    public function registro(RegistroRequest $request)
    {
        $datos = $request->validated();

        $cliente = Usuario::create([
            'rol_id' => Rol::firstOrCreate(['nombre' => 'cliente'])->id,
            'nombre' => $datos['nombre'],
            'email' => $datos['email'],
            'password_hash' => Hash::make($datos['password']),
            'fecha_registro' => now(),
        ]);

        return $this->creado([
            'token_type' => 'Bearer',
            'token' => $cliente->createToken('api-cliente', ['cliente'])->plainTextToken,
            'cliente' => $cliente->load('rol'),
        ], 'Cuenta de cliente creada correctamente.');
    }

    public function login(Request $request)
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        $cliente = Usuario::with('rol')->where('email', $credenciales['email'])->first();

        // Mensaje genérico: no revela si el correo existe ni si pertenece a otra área.
        if (! $cliente
            || ! $cliente->password_hash
            || ! Hash::check($credenciales['password'], $cliente->password_hash)
            || ! $cliente->esCliente()) {
            return $this->error('Correo o contraseña incorrectos.', 401);
        }

        return $this->ok([
            'token_type' => 'Bearer',
            'token' => $cliente->createToken('api-cliente', ['cliente'])->plainTextToken,
            'cliente' => $cliente,
        ], 'Inicio de sesión exitoso.');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->ok(null, 'Sesión cerrada correctamente.');
    }
}
