<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Api\V1\Concerns\RespuestasApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\ClientProfileRequest;
use App\Http\Resources\Client\ClienteResource;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/** /api/v1/client/perfil: siempre el perfil del usuario autenticado (sin id en la URL). */
class PerfilController extends Controller
{
    use RespuestasApi;

    public function show(Request $request)
    {
        return new ClienteResource($request->user()->load('rol'));
    }

    public function update(ClientProfileRequest $request)
    {
        $cliente = $request->user();
        $datos = $request->validated();

        if (isset($datos['password'])) {
            if (! Hash::check($datos['password_actual'], $cliente->password_hash)) {
                throw ValidationException::withMessages(['password_actual' => ['La contraseña actual no es correcta.']]);
            }

            $datos['password_hash'] = Hash::make($datos['password']);
        }

        // Solo estos campos pueden cambiar; rol y demás columnas internas se ignoran.
        $cliente->fill(Arr::only($datos, ['nombre', 'email', 'password_hash']))->save();

        return (new ClienteResource($cliente->load('rol')))->additional(['message' => 'Perfil actualizado correctamente.']);
    }
}
