<?php

namespace App\Http\Middleware;

use App\Models\Usuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deja pasar únicamente a clientes registrados (token emitido con la habilidad "cliente"
 * y cuyo usuario conserva el rol "cliente").
 */
class SoloCliente
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (! $usuario instanceof Usuario || ! $usuario->tokenCan('cliente') || ! $usuario->esCliente()) {
            return response()->json([
                'message' => 'Esta operación es exclusiva del área de clientes.',
            ], 403);
        }

        return $next($request);
    }
}
