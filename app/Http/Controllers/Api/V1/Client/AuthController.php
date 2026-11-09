<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Api\V1\Concerns\RespuestasApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\ClientLoginRequest;
use App\Http\Requests\Client\ClientRegisterRequest;
use App\Http\Resources\Client\ClienteResource;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/** /api/v1/client/register · login · logout (Laravel Sanctum). */
class AuthController extends Controller
{
    use RespuestasApi;

    /** Autorregistro: el rol siempre es "cliente" y nunca se toma de la solicitud. */
    public function register(ClientRegisterRequest $request)
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
            'token' => $cliente->createToken('client-token', ['cliente'])->plainTextToken,
            'cliente' => new ClienteResource($cliente->load('rol')),
        ], 'Cuenta de cliente creada correctamente.');
    }

    public function login(ClientLoginRequest $request)
    {
        $datos = $request->validated();
        $cliente = Usuario::with('rol')->where('email', $datos['email'])->first();

        // Mensaje genérico: no revela si el correo existe ni si pertenece a otra área.
        if (! $cliente
            || ! $cliente->password_hash
            || ! Hash::check($datos['password'], $cliente->password_hash)
            || ! $cliente->esCliente()) {
            return $this->error('Correo o contraseña incorrectos.', 401);
        }

        if ($cliente->activo === false) {
            return $this->error('Esta cuenta se encuentra deshabilitada.', 403);
        }

        return $this->ok([
            'token_type' => 'Bearer',
            'token' => $cliente->createToken('client-token', ['cliente'])->plainTextToken,
            'cliente' => new ClienteResource($cliente),
        ], 'Inicio de sesión exitoso.');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->ok(null, 'Sesión cerrada correctamente.');
    }
}
