<?php

use App\Http\Controllers\Api\V1\CategoriaController;
use App\Http\Controllers\Api\V1\ProductoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 · Actividad 4.2 (sin autenticación ni autorización)
|--------------------------------------------------------------------------
| Prefijo automático /api + 'v1'. Los identificadores se reciben como {id} y
| cada controlador los localiza con find() (sin route model binding).
*/
Route::prefix('v1')->name('v1.')->group(function () {

    Route::apiResource('categorias', CategoriaController::class)->parameters(['categorias' => 'id']);

    Route::apiResource('productos', ProductoController::class)->parameters(['productos' => 'id']);
    Route::post('productos/{id}/imagen', [ProductoController::class, 'guardarImagen'])->name('productos.imagen.guardar');
    Route::delete('productos/{id}/imagen', [ProductoController::class, 'eliminarImagen'])->name('productos.imagen.eliminar');
});
