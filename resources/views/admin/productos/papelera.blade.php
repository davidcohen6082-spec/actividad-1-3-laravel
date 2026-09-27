@extends('layout.admin')
@section('titulo', 'Papelera de Productos')
@section('contenido')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Papelera — Productos</h1>
    <a href="{{ route('admin.productos.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; Volver al listado</a>
</div>
<div class="relative overflow-x-auto shadow-sm rounded-lg">
    <table class="w-full text-sm text-left text-gray-500">
        <thead class="text-xs text-gray-700 uppercase bg-gray-100">
            <tr>
                <th class="px-6 py-3">ID</th>
                <th class="px-6 py-3">Nombre</th>
                <th class="px-6 py-3">Eliminado el</th>
                <th class="px-6 py-3">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($productos as $producto)
                <tr class="bg-white border-b">
                    <td class="px-6 py-4">{{ $producto->id }}</td>
                    <td class="px-6 py-4">{{ $producto->nombre }}</td>
                    <td class="px-6 py-4">{{ $producto->deleted_at->format('Y-m-d H:i') }}</td>
                    <td class="px-6 py-4 space-x-2">
                        <form action="{{ route('admin.productos.restaurar', $producto->id) }}" method="POST" class="inline-block"
                            onsubmit="return confirm('¿Restaurar el producto &quot;{{ $producto->nombre }}&quot;?');">
                            @csrf @method('PUT')
                            <button type="submit" class="font-medium text-green-600 hover:underline bg-transparent border-0 p-0 cursor-pointer">Restaurar</button>
                        </form>
                        <form action="{{ route('admin.productos.forceDestroy', $producto->id) }}" method="POST" class="inline-block"
                            onsubmit="return confirm('Esta acción es permanente. ¿Eliminar definitivamente &quot;{{ $producto->nombre }}&quot;?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="font-medium text-red-600 hover:underline bg-transparent border-0 p-0 cursor-pointer">Eliminar definitivamente</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr class="bg-white border-b"><td class="px-6 py-4" colspan="4">No hay productos eliminados.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $productos->links() }}</div>
@endsection