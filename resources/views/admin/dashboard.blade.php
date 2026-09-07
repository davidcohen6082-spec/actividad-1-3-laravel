@extends('layout.admin')
@section('titulo', 'Inicio')
@section('contenido')
<h1 class="text-2xl font-bold mb-6">Panel Administrativo</h1>
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div class="p-4 bg-white rounded-lg border border-gray-200 shadow-sm">
        <p class="text-sm text-gray-500">Usuarios registrados</p>
        <p class="text-2xl font-bold">128</p>
    </div>
    <div class="p-4 bg-white rounded-lg border border-gray-200 shadow-sm">
        <p class="text-sm text-gray-500">Productos publicados</p>
        <p class="text-2xl font-bold">342</p>
    </div>
    <div class="p-4 bg-white rounded-lg border border-gray-200 shadow-sm">
        <p class="text-sm text-gray-500">Órdenes completadas</p>
        <p class="text-2xl font-bold">57</p>
    </div>
    <div class="p-4 bg-white rounded-lg border border-gray-200 shadow-sm">
        <p class="text-sm text-gray-500">Roles activos</p>
        <p class="text-2xl font-bold">2</p>
    </div>
</div>
<p class="mt-6 text-gray-600">Usa el menú de la izquierda para navegar entre los módulos.</p>
@endsection