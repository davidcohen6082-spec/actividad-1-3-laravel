@extends('layout.admin')
@section('titulo', 'Formulario de Producto')
@section('contenido')
<h1 class="text-2xl font-bold mb-6">Nuevo Producto</h1>
<form class="max-w-lg bg-white p-6 rounded-lg border border-gray-200 shadow-sm space-y-4">
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-900">Nombre</label>
        <input type="text" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5" placeholder="Ej. Camisa azul talla M">
    </div>
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-900">Descripción</label>
        <textarea rows="3" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5" placeholder="Detalles del artículo..."></textarea>
    </div>
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-900">Categoría</label>
        <select class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5">
            <option>Camisas</option>
            <option>Pantalones</option>
            <option>Calzado</option>
            <option>Accesorios</option>
        </select>
    </div>
    <div>
        <span class="block mb-2 text-sm font-medium text-gray-900">Condición</span>
        <div class="flex items-center gap-4">
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="radio" name="condicion" class="w-4 h-4"> Nuevo
            </label>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="radio" name="condicion" class="w-4 h-4"> Usado
            </label>
        </div>
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block mb-2 text-sm font-medium text-gray-900">Precio</label>
            <input type="number" step="0.01" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5" placeholder="0.00">
        </div>
        <div>
            <label class="block mb-2 text-sm font-medium text-gray-900">Stock</label>
            <input type="number" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5" placeholder="1">
        </div>
    </div>
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-900">Imagen del artículo</label>
        <input type="file" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50">
    </div>
    <button type="button" class="text-white bg-indigo-600 hover:bg-indigo-700 font-medium rounded-lg text-sm px-5 py-2.5">Guardar</button>
</form>
@endsection