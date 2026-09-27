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
    Route::get('/roles/papelera', [RolController::class, 'papelera'])->name('roles.papelera');
    Route::get('/roles/nuevo', [RolController::class, 'create'])->name('roles.form');
    Route::post('/roles', [RolController::class, 'store'])->name('roles.store');
    Route::get('/roles/{id}', [RolController::class, 'show'])->name('roles.show');
    Route::get('/roles/{id}/editar', [RolController::class, 'edit'])->name('roles.edit');
    Route::put('/roles/{id}', [RolController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{id}', [RolController::class, 'destroy'])->name('roles.destroy');
    Route::put('/roles/{id}/restaurar', [RolController::class, 'restaurar'])->name('roles.restaurar');
    Route::delete('/roles/{id}/definitivo', [RolController::class, 'forceDestroy'])->name('roles.forceDestroy');

    Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
    Route::get('/usuarios/papelera', [UsuarioController::class, 'papelera'])->name('usuarios.papelera');
    Route::get('/usuarios/nuevo', [UsuarioController::class, 'create'])->name('usuarios.form');
    Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
    Route::get('/usuarios/{id}', [UsuarioController::class, 'show'])->name('usuarios.show');
    Route::get('/usuarios/{id}/editar', [UsuarioController::class, 'edit'])->name('usuarios.edit');
    Route::put('/usuarios/{id}', [UsuarioController::class, 'update'])->name('usuarios.update');
    Route::delete('/usuarios/{id}', [UsuarioController::class, 'destroy'])->name('usuarios.destroy');
    Route::put('/usuarios/{id}/restaurar', [UsuarioController::class, 'restaurar'])->name('usuarios.restaurar');
    Route::delete('/usuarios/{id}/definitivo', [UsuarioController::class, 'forceDestroy'])->name('usuarios.forceDestroy');

    Route::get('/productos', [ProductoController::class, 'index'])->name('productos.index');
    Route::get('/productos/papelera', [ProductoController::class, 'papelera'])->name('productos.papelera');
    Route::get('/productos/nuevo', [ProductoController::class, 'create'])->name('productos.form');
    Route::post('/productos', [ProductoController::class, 'store'])->name('productos.store');
    Route::get('/productos/{id}', [ProductoController::class, 'show'])->name('productos.show');
    Route::get('/productos/{id}/editar', [ProductoController::class, 'edit'])->name('productos.edit');
    Route::put('/productos/{id}', [ProductoController::class, 'update'])->name('productos.update');
    Route::delete('/productos/{id}', [ProductoController::class, 'destroy'])->name('productos.destroy');
    Route::put('/productos/{id}/restaurar', [ProductoController::class, 'restaurar'])->name('productos.restaurar');
    Route::delete('/productos/{id}/definitivo', [ProductoController::class, 'forceDestroy'])->name('productos.forceDestroy');

    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
    Route::get('/logs/papelera', [LogController::class, 'papelera'])->name('logs.papelera');
    Route::get('/logs/nuevo', [LogController::class, 'create'])->name('logs.form');
    Route::post('/logs', [LogController::class, 'store'])->name('logs.store');
    Route::get('/logs/{id}', [LogController::class, 'show'])->name('logs.show');
    Route::get('/logs/{id}/editar', [LogController::class, 'edit'])->name('logs.edit');
    Route::put('/logs/{id}', [LogController::class, 'update'])->name('logs.update');
    Route::delete('/logs/{id}', [LogController::class, 'destroy'])->name('logs.destroy');
    Route::put('/logs/{id}/restaurar', [LogController::class, 'restaurar'])->name('logs.restaurar');
    Route::delete('/logs/{id}/definitivo', [LogController::class, 'forceDestroy'])->name('logs.forceDestroy');
});