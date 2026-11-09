<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Support\ApiRespuesta;
use Illuminate\Http\JsonResponse;

trait ValidaIdentificador
{
    /**
     * Un identificador válido es un entero positivo. Si no lo es se responde 400
     * (parámetro incorrecto) antes de consultar la base de datos.
     */
    protected function idInvalido(mixed $id): ?JsonResponse
    {
        if (! is_string($id) && ! is_int($id)) {
            return ApiRespuesta::error('El identificador enviado no es válido', 400);
        }

        if (! ctype_digit((string) $id) || (int) $id < 1) {
            return ApiRespuesta::error('El identificador debe ser un número entero positivo', 400, [
                'id' => ['El identificador debe ser un número entero positivo.'],
            ]);
        }

        return null;
    }
}
