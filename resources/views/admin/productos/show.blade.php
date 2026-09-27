@extends('layout.admin')
@section('titulo', 'Detalle del Producto')
@section('contenido')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Detalle del Producto</h1>
    <a href="{{ route('admin.productos.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; Volver al listado</a>
</div>
<div class="max-w-lg bg-white p-6 rounded-lg border border-gray-200 shadow-sm space-y-3">
    @if ($producto->imagen)
        <img src="{{ asset($producto->imagen) }}" class="w-32 h-32 rounded object-cover border border-gray-200" alt="{{ $producto->nombre }}">
    @endif
    <div><span class="font-medium text-gray-700">ID:</span> {{ $producto->id }}</div>
    <div><span class="font-medium text-gray-700">Nombre:</span> {{ $producto->nombre }}</div>
    <div><span class="font-medium text-gray-700">Descripción:</span> {{ $producto->descripcion ?: '—' }}</div>
    <div><span class="font-medium text-gray-700">Talla:</span> {{ $producto->talla ?: '—' }}</div>
    <div><span class="font-medium text-gray-700">Condición:</span> {{ ucfirst($producto->condicion) }}</div>
    <div><span class="font-medium text-gray-700">Precio:</span> ${{ number_format($producto->precio, 2) }}</div>
    <div><span class="font-medium text-gray-700">Stock:</span> {{ $producto->stock }}</div>
    <div><span class="font-medium text-gray-700">Categoría:</span> {{ $producto->categoria->nombre ?? 'Sin categoría' }}</div>
    <div><span class="font-medium text-gray-700">Publicado por:</span> {{ $producto->usuario->nombre ?? 'Desconocido' }}</div>
</div>
@endsection