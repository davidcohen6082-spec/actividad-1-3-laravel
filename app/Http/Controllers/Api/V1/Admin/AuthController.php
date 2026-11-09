<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Concerns\RespuestasApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use App\Models\Administrador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use RespuestasApi;

    public function login(AdminLoginRequest $request)
    {
        $credenciales = $request->validated();

        $administrador = Administrador::where('usuario', $credenciales['usuario'])->first();

        if (! $administrador || ! Hash::check($credenciales['password'], $administrador->password)) {
            return $this->error('Usuario o contraseña incorrectos.', 401);
        }

        if (! $administrador->activo) {
            return $this->error('Esta cuenta se encuentra deshabilitada.', 403);
        }

        // El token nace con la habilidad "admin": no sirve en las rutas del área cliente.
        $token = $administrador->createToken('admin-token', ['admin'])->plainTextToken;

        return $this->ok([
            'token_type' => 'Bearer',
            'token' => $token,
            'administrador' => $this->datos($administrador),
        ], 'Inicio de sesión exitoso.');
    }

    public function perfil(Request $request)
    {
        return $this->ok($this->datos($request->user()));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->ok(null, 'Sesión cerrada correctamente.');
    }

    private function datos(Administrador $admin): array
    {
        return [
            'id' => $admin->id,
            'nombre_completo' => $admin->nombre_completo,
            'usuario' => $admin->usuario,
            'nivel' => $admin->nivel,
        ];
    }
}
