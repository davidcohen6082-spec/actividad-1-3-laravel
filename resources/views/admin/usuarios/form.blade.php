@extends('layout.admin')
@section('titulo', 'Formulario de Usuario')
@section('contenido')
<h1 class="text-2xl font-bold mb-6">Nuevo Usuario</h1>
<form class="max-w-lg bg-white p-6 rounded-lg border border-gray-200 shadow-sm space-y-4">
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-900">Nombre completo</label>
        <input type="text" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5" placeholder="Ej. Ana Pérez">
    </div>
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-900">Correo electrónico</label>
        <input type="email" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5" placeholder="correo@ejemplo.com">
    </div>
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-900">Rol</label>
        <select class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5">
            <option>Cliente</option>
            <option>Administrador</option>
        </select>
    </div>
    <div>
        <span class="block mb-2 text-sm font-medium text-gray-900">Tipo de inicio de sesión</span>
        <div class="flex items-center gap-4">
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="radio" name="login_tipo" class="w-4 h-4"> Local
            </label>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="radio" name="login_tipo" class="w-4 h-4"> Social (Google/Facebook)
            </label>
        </div>
    </div>
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-900">Foto de perfil</label>
        <input type="file" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50">
    </div>
    <button type="button" class="text-white bg-indigo-600 hover:bg-indigo-700 font-medium rounded-lg text-sm px-5 py-2.5">Guardar</button>
</form>
@endsection