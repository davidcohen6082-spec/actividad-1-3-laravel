<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Convierte cualquier excepción de la API en la respuesta JSON estandarizada.
 * Nunca expone trazas, consultas SQL, rutas internas ni credenciales:
 * el detalle técnico lo registra Laravel en storage/logs.
 */
class ManejadorErroresApi
{
    public static function responder(Throwable $e): JsonResponse
    {
        if ($e instanceof ValidationException) {
            return ApiRespuesta::error('Los datos enviados no son válidos', 422, $e->errors());
        }

        if ($e instanceof HttpExceptionInterface) {
            $estado = $e->getStatusCode();

            $mensajes = [
                400 => 'La solicitud es incorrecta o está mal formada',
                404 => 'El recurso solicitado no existe',
                405 => 'El método HTTP no está permitido para este endpoint',
                413 => 'El archivo o la solicitud excede el tamaño permitido',
                415 => 'El tipo de contenido enviado no es admitido',
                429 => 'Se realizaron demasiadas solicitudes, intenta más tarde',
            ];

            if (isset($mensajes[$estado])) {
                return ApiRespuesta::error($mensajes[$estado], $estado);
            }

            if ($estado < 500) {
                return ApiRespuesta::error('No fue posible procesar la solicitud', $estado);
            }
        }

        return ApiRespuesta::error('Ocurrió un error interno al procesar la solicitud', 500);
    }
}
