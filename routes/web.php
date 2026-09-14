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
    Route::view('/roles/nuevo', 'admin.roles.form')->name('roles.form');

    Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
    Route::view('/usuarios/nuevo', 'admin.usuarios.form')->name('usuarios.form');

    Route::get('/productos', [ProductoController::class, 'index'])->name('productos.index');
    Route::view('/productos/nuevo', 'admin.productos.form')->name('productos.form');

    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
    Route::view('/logs/nuevo', 'admin.logs.form')->name('logs.form');
});