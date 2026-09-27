@extends('layout.admin')
@section('titulo', 'Productos')
@section('contenido')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Productos</h1>
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.productos.papelera') }}" class="text-sm text-gray-600 hover:underline">🗑 Papelera</a>
        <a href="{{ route('admin.productos.form') }}" class="text-white bg-indigo-600 hover:bg-indigo-700 font-medium rounded-lg text-sm px-4 py-2">+ Nuevo</a>
    </div>
</div>
<div class="relative overflow-x-auto shadow-sm rounded-lg">
    <table class="w-full text-sm text-left text-gray-500">
        <thead class="text-xs text-gray-700 uppercase bg-gray-100">
            <tr>
                <th class="px-6 py-3">ID</th>
                <th class="px-6 py-3">Nombre</th>
                <th class="px-6 py-3">Categoría</th>
                <th class="px-6 py-3">Publicado por</th>
                <th class="px-6 py-3">Precio</th>
                <th class="px-6 py-3">Stock</th>
                <th class="px-6 py-3">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($productos as $producto)
                <tr class="bg-white border-b">
                    <td class="px-6 py-4">{{ $producto->id }}</td>
                    <td class="px-6 py-4">{{ $producto->nombre }}</td>
                    <td class="px-6 py-4">{{ $producto->categoria->nombre ?? 'Sin categoría' }}</td>
                    <td class="px-6 py-4">{{ $producto->usuario->nombre ?? 'Desconocido' }}</td>
                    <td class="px-6 py-4">${{ number_format($producto->precio, 2) }}</td>
                    <td class="px-6 py-4">{{ $producto->stock }}</td>
                    <td class="px-6 py-4 space-x-2">
                        <a href="{{ route('admin.productos.show', $producto->id) }}" class="font-medium text-blue-600 hover:underline">Consultar</a>
                        <a href="{{ route('admin.productos.edit', $producto->id) }}" class="font-medium text-yellow-600 hover:underline">Editar</a>
                        <form action="{{ route('admin.productos.destroy', $producto->id) }}" method="POST" class="inline-block"
                            onsubmit="return confirm('¿Eliminar el producto &quot;{{ $producto->nombre }}&quot;? Podrás restaurarlo después desde la papelera.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="font-medium text-red-600 hover:underline bg-transparent border-0 p-0 cursor-pointer">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $productos->links() }}</div>
@endsection