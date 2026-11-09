<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

/**
 * Middleware de la API (no es autenticación):
 *  1. Marca la petición como "espera JSON", aunque el cliente no envíe el encabezado Accept.
 *  2. Rechaza con 415 los cuerpos con un Content-Type que la API no sabe leer.
 *  3. Rechaza con 400 los cuerpos JSON mal formados.
 */
class ApiJson
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'], true) && $request->headers->has('Content-Type')) {
            $tipo = strtolower((string) $request->header('Content-Type'));

            $admitido = $request->isJson()
                || str_contains($tipo, 'multipart/form-data')
                || str_contains($tipo, 'application/x-www-form-urlencoded');

            if (! $admitido) {
                throw new UnsupportedMediaTypeHttpException('Tipo de contenido no admitido.');
            }

            // Laravel ignora en silencio un JSON mal formado; aquí se responde 400.
            if ($request->isJson() && trim($request->getContent()) !== '') {
                json_decode($request->getContent());

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new BadRequestHttpException('JSON mal formado.');
                }
            }
        }

        return $next($request);
    }
}
