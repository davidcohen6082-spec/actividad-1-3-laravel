@extends('layout.admin')
@section('titulo', 'Productos')
@section('contenido')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Productos</h1>
    <a href="{{ route('admin.productos.form') }}" class="text-white bg-indigo-600 hover:bg-indigo-700 font-medium rounded-lg text-sm px-4 py-2">+ Nuevo</a>
</div>
<div class="relative overflow-x-auto shadow-sm rounded-lg">
    <table class="w-full text-sm text-left text-gray-500">
        <thead class="text-xs text-gray-700 uppercase bg-gray-100">
            <tr>
                <th class="px-6 py-3">ID</th>
                <th class="px-6 py-3">Nombre</th>
                <th class="px-6 py-3">Categoría</th>
                <th class="px-6 py-3">Precio</th>
                <th class="px-6 py-3">Stock</th>
                <th class="px-6 py-3">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <tr class="bg-white border-b">
                <td class="px-6 py-4">1</td>
                <td class="px-6 py-4">Camisa azul talla M</td>
                <td class="px-6 py-4">Camisas</td>
                <td class="px-6 py-4">$50.00</td>
                <td class="px-6 py-4">1</td>
                <td class="px-6 py-4 space-x-2">
                    <a href="#" class="font-medium text-blue-600 hover:underline">Consultar</a>
                    <a href="{{ route('admin.productos.form') }}" class="font-medium text-yellow-600 hover:underline">Editar</a>
                    <a href="#" class="font-medium text-red-600 hover:underline">Eliminar</a>
                </td>
            </tr>
            <tr class="bg-white border-b">
                <td class="px-6 py-4">2</td>
                <td class="px-6 py-4">Zapatos deportivos talla 26</td>
                <td class="px-6 py-4">Calzado</td>
                <td class="px-6 py-4">$180.00</td>
                <td class="px-6 py-4">2</td>
                <td class="px-6 py-4 space-x-2">
                    <a href="#" class="font-medium text-blue-600 hover:underline">Consultar</a>
                    <a href="{{ route('admin.productos.form') }}" class="font-medium text-yellow-600 hover:underline">Editar</a>
                    <a href="#" class="font-medium text-red-600 hover:underline">Eliminar</a>
                </td>
            </tr>
        </tbody>
    </table>
</div>
@endsection