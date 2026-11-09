<?php

namespace App\Http\Middleware;

use App\Models\Administrador;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deja pasar únicamente a identidades de tipo Administrador (token emitido con la
 * habilidad "admin"). Un token de cliente nunca puede usar las rutas administrativas,
 * aunque haya sido emitido por el mismo sistema (Sanctum).
 */
class SoloAdministrador
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (! $usuario instanceof Administrador || ! $usuario->tokenCan('admin')) {
            return response()->json([
                'message' => 'Esta operación es exclusiva del área administrativa.',
            ], 403);
        }

        // Si la cuenta se deshabilita después de emitir el token, el token deja de servir.
        if (! $usuario->activo) {
            return response()->json([
                'message' => 'Esta cuenta se encuentra deshabilitada.',
            ], 403);
        }

        return $next($request);
    }
}
