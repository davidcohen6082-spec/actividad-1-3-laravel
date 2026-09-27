@extends('layout.admin')
@section('titulo', 'Detalle del Rol')
@section('contenido')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Detalle del Rol</h1>
    <a href="{{ route('admin.roles.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; Volver al listado</a>
</div>
<div class="max-w-lg bg-white p-6 rounded-lg border border-gray-200 shadow-sm space-y-3">
    <div><span class="font-medium text-gray-700">ID:</span> {{ $rol->id }}</div>
    <div><span class="font-medium text-gray-700">Nombre:</span> {{ $rol->nombre }}</div>
    <div><span class="font-medium text-gray-700">Usuarios asociados:</span> {{ $rol->usuarios_count }}</div>
    <div><span class="font-medium text-gray-700">Creado:</span> {{ $rol->created_at->format('Y-m-d H:i') }}</div>
</div>
@endsection