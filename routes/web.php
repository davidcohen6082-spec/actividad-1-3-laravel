<?php

/**
 * Cohen Napoles David
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RolController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\LogController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/admin', function () {
    return view('admin');
});

Route::get('/cliente', function () {
    return view('cliente');
});

Route::view('/plantilla','/layout/app');
Route::view('/catalogo','/catalogo/mascotas');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::view('/', 'admin.dashboard')->name('dashboard');
    Route::view('/contacto', 'admin.contacto')->name('contacto');

    Route::get('/roles', [RolController::class, 'index'])->name('roles.index');
    Route::get('/roles/nuevo', [RolController::class, 'create'])->name('roles.form');
    Route::post('/roles', [RolController::class, 'store'])->name('roles.store');

    Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
    Route::get('/usuarios/nuevo', [UsuarioController::class, 'create'])->name('usuarios.form');
    Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');

    Route::get('/productos', [ProductoController::class, 'index'])->name('productos.index');
    Route::get('/productos/nuevo', [ProductoController::class, 'create'])->name('productos.form');
    Route::post('/productos', [ProductoController::class, 'store'])->name('productos.store');

    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
    Route::get('/logs/nuevo', [LogController::class, 'create'])->name('logs.form');
    Route::post('/logs', [LogController::class, 'store'])->name('logs.store');
});