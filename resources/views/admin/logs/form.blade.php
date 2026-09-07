@extends('layout.admin')
@section('titulo', 'Formulario de Log')
@section('contenido')
<h1 class="text-2xl font-bold mb-6">Registrar Evento</h1>
<form class="max-w-md bg-white p-6 rounded-lg border border-gray-200 shadow-sm space-y-4">
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-900">Usuario</label>
        <select class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5">
            <option>Ana Pérez</option>
            <option>Luis Gómez</option>
        </select>
    </div>
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-900">Acción</label>
        <input type="text" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5" placeholder="Ej. Publicó un producto">
    </div>
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-900">Fecha</label>
        <input type="date" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5">
    </div>
    <div class="flex items-center gap-2">
        <input type="checkbox" class="w-4 h-4">
        <label class="text-sm text-gray-700">Marcar como evento crítico</label>
    </div>
    <button type="button" class="text-white bg-indigo-600 hover:bg-indigo-700 font-medium rounded-lg text-sm px-5 py-2.5">Guardar</button>
</form>
@endsection