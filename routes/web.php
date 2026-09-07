<?php

/**
 * Cohen Napoles David
 */

use Illuminate\Support\Facades\Route;

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

    Route::view('/roles', 'admin.roles.index')->name('roles.index');
    Route::view('/roles/nuevo', 'admin.roles.form')->name('roles.form');

    Route::view('/usuarios', 'admin.usuarios.index')->name('usuarios.index');
    Route::view('/usuarios/nuevo', 'admin.usuarios.form')->name('usuarios.form');

    Route::view('/productos', 'admin.productos.index')->name('productos.index');
    Route::view('/productos/nuevo', 'admin.productos.form')->name('productos.form');

    Route::view('/logs', 'admin.logs.index')->name('logs.index');
    Route::view('/logs/nuevo', 'admin.logs.form')->name('logs.form');
});