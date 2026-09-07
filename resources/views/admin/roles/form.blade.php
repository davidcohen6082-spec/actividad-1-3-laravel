@extends('layout.admin')
@section('titulo', 'Formulario de Rol')
@section('contenido')
<h1 class="text-2xl font-bold mb-6">Nuevo Rol</h1>
<form class="max-w-md bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
    <div class="mb-4">
        <label class="block mb-2 text-sm font-medium text-gray-900">Nombre del rol</label>
        <input type="text" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5" placeholder="Ej. cliente">
    </div>
    <button type="button" class="text-white bg-indigo-600 hover:bg-indigo-700 font-medium rounded-lg text-sm px-5 py-2.5">Guardar</button>
</form>
@endsection