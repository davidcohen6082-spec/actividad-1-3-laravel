<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Estructura uniforme de respuestas JSON de la API v1:
 *   éxito  -> { "message": "...", "data": ... }   (message y data son opcionales)
 *   listas -> estructura de paginación de Laravel (data, links, meta)
 *   error  -> { "message": "...", "errors": { ... } }  (errors solo en 422)
 */
trait RespuestasApi
{
    protected function ok(mixed $data = null, ?string $mensaje = null, int $estado = 200): JsonResponse
    {
        $cuerpo = [];

        if ($mensaje !== null) {
            $cuerpo['message'] = $mensaje;
        }

        if ($data !== null) {
            $cuerpo['data'] = $data;
        }

        return response()->json($cuerpo, $estado);
    }

    protected function creado(mixed $data, string $mensaje): JsonResponse
    {
        return $this->ok($data, $mensaje, 201);
    }

    protected function sinContenido(): JsonResponse
    {
        return response()->json(null, 204);
    }

    protected function error(string $mensaje, int $estado): JsonResponse
    {
        return response()->json(['message' => $mensaje], $estado);
    }

    /**
     * Autorización por Gate (reglas generales). Siempre se evalúa sobre el usuario
     * autenticado por el token, nunca sobre datos enviados por el cliente.
     */
    protected function permitir(Request $request, string $habilidad, string $mensaje = 'No tienes autorización para realizar esta operación.'): void
    {
        abort_unless($request->user()->can($habilidad), 403, $mensaje);
    }

    /**
     * Autorización por Policy (reglas sobre un registro concreto).
     */
    protected function permitirSobre(Request $request, string $habilidad, mixed $registro, string $mensaje = 'No tienes autorización para acceder a este recurso.'): void
    {
        abort_unless($request->user()->can($habilidad, $registro), 403, $mensaje);
    }

    /**
     * Formato único para todas las colecciones paginadas: { data, links, meta }.
     */
    protected function paginado(LengthAwarePaginator $paginador): JsonResponse
    {
        return response()->json([
            'data' => $paginador->items(),
            'links' => [
                'first' => $paginador->url(1),
                'last' => $paginador->url($paginador->lastPage()),
                'prev' => $paginador->previousPageUrl(),
                'next' => $paginador->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $paginador->currentPage(),
                'per_page' => $paginador->perPage(),
                'total' => $paginador->total(),
                'last_page' => $paginador->lastPage(),
            ],
        ]);
    }

    protected function porPagina(Request $request, int $predeterminado = 10): int
    {
        return max(1, min((int) $request->query('per_page', $predeterminado), 50));
    }
}
