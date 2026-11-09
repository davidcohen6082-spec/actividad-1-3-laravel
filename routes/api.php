<?php

use App\Http\Controllers\Api\V1\Admin;
use App\Http\Controllers\Api\V1\CatalogoController;
use App\Http\Controllers\Api\V1\Cliente;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — Sistema de Gestión para Bancos de Ropa y Artículos de Segunda Mano
|--------------------------------------------------------------------------
| Prefijo automático de Laravel: /api  ->  URL final: /api/v1/...
|
|  Público   /api/v1/productos, /api/v1/categorias        (sin autenticación)
|  Admin     /api/v1/admin/...    token Sanctum con habilidad "admin"   + Gates por nivel
|  Cliente   /api/v1/cliente/...  token Sanctum con habilidad "cliente" + Policies
|
| El prefijo "admin"/"cliente" NO es la medida de seguridad: la protección real la dan los
| middleware auth:sanctum + solo.admin / solo.cliente y las reglas de autorización.
*/

Route::prefix('v1')->group(function () {

    // ------------------------------------------------------------------ PÚBLICO
    Route::get('productos', [CatalogoController::class, 'productos'])->name('v1.catalogo.productos');
    Route::get('productos/{id}', [CatalogoController::class, 'producto'])->whereNumber('id')->name('v1.catalogo.producto');
    Route::get('categorias', [CatalogoController::class, 'categorias'])->name('v1.catalogo.categorias');

    // ------------------------------------------------------------ ÁREA ADMINISTRATIVA
    Route::prefix('admin')->group(function () {
        Route::post('login', [Admin\AuthController::class, 'login'])
            ->middleware('throttle:auth-api')
            ->name('v1.admin.login');

        Route::middleware(['auth:sanctum', 'solo.admin'])->group(function () {
            Route::get('perfil', [Admin\AuthController::class, 'perfil'])->name('v1.admin.perfil');
            Route::post('logout', [Admin\AuthController::class, 'logout'])->name('v1.admin.logout');

            // CRUD + papelera. La papelera se modela como subrecurso (sustantivo):
            //   GET    /admin/{recurso}/papelera         lista los eliminados
            //   PATCH  /admin/{recurso}/papelera/{id}    restaura
            //   DELETE /admin/{recurso}/papelera/{id}    elimina definitivamente
            $recurso = function (string $nombre, string $controlador, bool $conPapelera = true) {
                if ($conPapelera) {
                    Route::get("{$nombre}/papelera", [$controlador, 'papelera'])->name("v1.admin.{$nombre}.papelera");
                    Route::patch("{$nombre}/papelera/{id}", [$controlador, 'restaurar'])->whereNumber('id')->name("v1.admin.{$nombre}.restaurar");
                    Route::delete("{$nombre}/papelera/{id}", [$controlador, 'eliminarDefinitivamente'])->whereNumber('id')->name("v1.admin.{$nombre}.definitivo");
                }

                Route::apiResource($nombre, $controlador)->parameters([$nombre => 'id'])->names("v1.admin.{$nombre}");
            };

            $recurso('roles', Admin\RolController::class);
            $recurso('usuarios', Admin\UsuarioController::class);
            $recurso('productos', Admin\ProductoController::class);
            $recurso('categorias', Admin\CategoriaController::class, conPapelera: false);
            $recurso('logs', Admin\LogController::class);

            // Pedidos: consulta y cambio de estado (los crea el cliente).
            Route::get('pedidos', [Admin\PedidoController::class, 'index'])->name('v1.admin.pedidos.index');
            Route::get('pedidos/{id}', [Admin\PedidoController::class, 'show'])->whereNumber('id')->name('v1.admin.pedidos.show');
            Route::patch('pedidos/{id}', [Admin\PedidoController::class, 'update'])->whereNumber('id')->name('v1.admin.pedidos.update');

            // Reportes
            Route::get('reportes/resumen', [Admin\ReporteController::class, 'resumen'])->name('v1.admin.reportes.resumen');
        });
    });

    // ----------------------------------------------------------------- ÁREA CLIENTE
    Route::prefix('cliente')->group(function () {
        Route::post('registro', [Cliente\AuthController::class, 'registro'])->middleware('throttle:auth-api')->name('v1.cliente.registro');
        Route::post('login', [Cliente\AuthController::class, 'login'])->middleware('throttle:auth-api')->name('v1.cliente.login');

        Route::middleware(['auth:sanctum', 'solo.cliente'])->group(function () {
            Route::post('logout', [Cliente\AuthController::class, 'logout'])->name('v1.cliente.logout');

            Route::get('perfil', [Cliente\PerfilController::class, 'show'])->name('v1.cliente.perfil.show');
            Route::patch('perfil', [Cliente\PerfilController::class, 'update'])->name('v1.cliente.perfil.update');

            Route::get('pedidos', [Cliente\PedidoController::class, 'index'])->name('v1.cliente.pedidos.index');
            Route::post('pedidos', [Cliente\PedidoController::class, 'store'])->name('v1.cliente.pedidos.store');
            Route::get('pedidos/{id}', [Cliente\PedidoController::class, 'show'])->whereNumber('id')->name('v1.cliente.pedidos.show');
            Route::patch('pedidos/{id}', [Cliente\PedidoController::class, 'update'])->whereNumber('id')->name('v1.cliente.pedidos.update');

            Route::get('carrito', [Cliente\CarritoController::class, 'show'])->name('v1.cliente.carrito.show');
            Route::post('carrito/items', [Cliente\CarritoController::class, 'agregar'])->name('v1.cliente.carrito.agregar');
            Route::patch('carrito/items/{id}', [Cliente\CarritoController::class, 'actualizar'])->whereNumber('id')->name('v1.cliente.carrito.actualizar');
            Route::delete('carrito/items/{id}', [Cliente\CarritoController::class, 'quitar'])->whereNumber('id')->name('v1.cliente.carrito.quitar');

            Route::get('lista-deseos', [Cliente\ListaDeseoController::class, 'index'])->name('v1.cliente.deseos.index');
            Route::post('lista-deseos', [Cliente\ListaDeseoController::class, 'store'])->name('v1.cliente.deseos.store');
            Route::delete('lista-deseos/{id}', [Cliente\ListaDeseoController::class, 'destroy'])->whereNumber('id')->name('v1.cliente.deseos.destroy');
        });
    });
});
