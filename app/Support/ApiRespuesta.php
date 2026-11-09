<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * Formato JSON estandarizado de TODA la API (éxito y error):
 *   { "success": bool, "message": string, "data": mixed|null, "errors": mixed|null }
 */
class ApiRespuesta
{
    public static function exito(string $mensaje, mixed $data = null, int $estado = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $mensaje,
            'data' => $data,
            'errors' => null,
        ], $estado);
    }

    public static function error(string $mensaje, int $estado, mixed $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $mensaje,
            'data' => null,
            'errors' => $errors,
        ], $estado);
    }
}
