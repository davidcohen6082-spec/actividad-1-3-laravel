<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Administrador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credenciales = $request->validate([
            'usuario' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'usuario.required' => 'El usuario es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        $administrador = Administrador::where('usuario', $credenciales['usuario'])->first();

        if (! $administrador || ! Hash::check($credenciales['password'], $administrador->password)) {
            return response()->json(['message' => 'Usuario o contraseña incorrectos.'], 401);
        }

        if (! $administrador->activo) {
            return response()->json(['message' => 'Esta cuenta se encuentra deshabilitada.'], 403);
        }

        $token = $administrador->createToken('postman')->plainTextToken;

        return response()->json([
            'message' => 'Inicio de sesión exitoso.',
            'token_type' => 'Bearer',
            'token' => $token,
            'administrador' => [
                'id' => $administrador->id,
                'nombre_completo' => $administrador->nombre_completo,
                'usuario' => $administrador->usuario,
                'nivel' => $administrador->nivel,
            ],
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente.'], 200);
    }

    public function perfil(Request $request)
    {
        $admin = $request->user();

        return response()->json([
            'id' => $admin->id,
            'nombre_completo' => $admin->nombre_completo,
            'usuario' => $admin->usuario,
            'nivel' => $admin->nivel,
        ], 200);
    }
}
