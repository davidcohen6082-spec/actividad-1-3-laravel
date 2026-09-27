@extends('layout.admin')
@section('titulo', 'Detalle del Usuario')
@section('contenido')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Detalle del Usuario</h1>
    <a href="{{ route('admin.usuarios.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; Volver al listado</a>
</div>
<div class="max-w-lg bg-white p-6 rounded-lg border border-gray-200 shadow-sm space-y-3">
    @if ($usuario->foto)
        <img src="{{ asset($usuario->foto) }}" class="w-20 h-20 rounded-full object-cover border border-gray-200" alt="Foto de {{ $usuario->nombre }}">
    @endif
    <div><span class="font-medium text-gray-700">ID:</span> {{ $usuario->id }}</div>
    <div><span class="font-medium text-gray-700">Nombre:</span> {{ $usuario->nombre }}</div>
    <div><span class="font-medium text-gray-700">Email:</span> {{ $usuario->email }}</div>
    <div><span class="font-medium text-gray-700">Rol:</span> {{ $usuario->rol->nombre ?? 'Sin rol' }}</div>
    <div><span class="font-medium text-gray-700">Tipo de inicio de sesión:</span> {{ $usuario->proveedor_social ?: 'Local' }}</div>
    <div><span class="font-medium text-gray-700">Fecha de registro:</span> {{ \Illuminate\Support\Carbon::parse($usuario->fecha_registro)->format('Y-m-d') }}</div>
</div>
@endsection