<?php

use App\Http\Middleware\SoloAdministrador;
use App\Http\Middleware\SoloCliente;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Solo las rutas web redirigen al login; la API responde 401 en JSON.
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return null;
            }

            return route('admin.login');
        });

        $middleware->alias([
            'solo.admin' => SoloAdministrador::class,
            'solo.cliente' => SoloCliente::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Todo lo que esté bajo /api responde JSON, aunque el cliente no envíe "Accept: application/json".
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'No autenticado. Envía un token Bearer válido en el encabezado Authorization.'], 401);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'El recurso solicitado no existe.'], 404);
            }
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($request->is('api/*')) {
                $mensajes = [
                    403 => 'No tienes autorización para realizar esta operación.',
                    405 => 'Método HTTP no permitido para este endpoint.',
                    429 => 'Demasiadas solicitudes. Intenta de nuevo en unos momentos.',
                ];

                $estado = $e->getStatusCode();
                $mensaje = $e->getMessage();

                // Los mensajes por defecto del framework vienen en inglés y revelan detalles internos.
                if ($mensaje === '' || in_array($estado, [405, 429]) || $mensaje === 'This action is unauthorized.') {
                    $mensaje = $mensajes[$estado] ?? 'Error en la solicitud.';
                }

                return response()->json(['message' => $mensaje], $estado, $e->getHeaders());
            }
        });
    })->create();
