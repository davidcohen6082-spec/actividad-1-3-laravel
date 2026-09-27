@extends('layout.admin')
@section('titulo', $rol ? 'Editar Rol' : 'Nuevo Rol')
@section('contenido')
<h1 class="text-2xl font-bold mb-6">{{ $rol ? 'Editar Rol' : 'Nuevo Rol' }}</h1>
<form action="{{ $rol ? route('admin.roles.update', $rol->id) : route('admin.roles.store') }}" method="POST"
    class="max-w-md bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
    @csrf
    @if ($rol) @method('PUT') @endif
    <div class="mb-4">
        <label class="block mb-2 text-sm font-medium text-gray-900">Nombre del rol</label>
        <input type="text" name="nombre" value="{{ old('nombre', $rol->nombre ?? '') }}" required minlength="3" maxlength="50"
            class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5 @error('nombre') border-red-500 @enderror"
            placeholder="Ej. cliente">
        @error('nombre') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <button type="submit" class="text-white bg-indigo-600 hover:bg-indigo-700 font-medium rounded-lg text-sm px-5 py-2.5">Guardar</button>
</form>
@endsection