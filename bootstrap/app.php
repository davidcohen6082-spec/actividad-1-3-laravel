<?php

use App\Http\Middleware\ApiJson;
use App\Http\Middleware\SoloAdministrador;
use App\Http\Middleware\SoloCliente;
use App\Support\ManejadorErroresApi;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // NUEVO (4.2): en /api/* siempre se responde JSON y se rechazan con 415 los
        // tipos de contenido no admitidos y con 400 los JSON mal formados.
        $middleware->api(prepend: [ApiJson::class]);

        // Solo las rutas web redirigen al login; la API nunca redirige.
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return null;
            }

            return route('admin.login');
        });

        // (Actividad 4.1) Ya no se usan en las rutas de la 4.2, se conservan para la parte web/4.1.
        $middleware->alias([
            'solo.admin' => SoloAdministrador::class,
            'solo.cliente' => SoloCliente::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // NUEVO (4.2): toda excepción bajo /api/* se devuelve como JSON estandarizado
        // {success, message, data, errors}. El detalle técnico solo va al log de Laravel.
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());

        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                return ManejadorErroresApi::responder($e);
            }

            return null; // la parte web conserva su comportamiento normal
        });
    })->create();
