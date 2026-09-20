@extends('layout.admin')
@section('titulo', 'Formulario de Log')
@section('contenido')
<h1 class="text-2xl font-bold mb-6">Registrar Evento</h1>
<form action="{{ route('admin.logs.store') }}" method="POST" class="max-w-md bg-white p-6 rounded-lg border border-gray-200 shadow-sm space-y-4">
    @csrf
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-900">Usuario</label>
        <select name="usuario_id" required
            class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5 @error('usuario_id') border-red-500 @enderror">
            <option value="">-- Selecciona un usuario --</option>
            @foreach ($usuarios as $usuario)
                <option value="{{ $usuario->id }}" @selected(old('usuario_id') == $usuario->id)>{{ $usuario->nombre }}</option>
            @endforeach
        </select>
        @error('usuario_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-900">Acción</label>
        <input type="text" name="accion" value="{{ old('accion') }}" required minlength="5" maxlength="150"
            class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5 @error('accion') border-red-500 @enderror"
            placeholder="Ej. Publicó un producto">
        @error('accion') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-900">Fecha</label>
        <input type="date" name="fecha" value="{{ old('fecha', now()->format('Y-m-d')) }}" required max="{{ now()->format('Y-m-d') }}"
            class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5 @error('fecha') border-red-500 @enderror">
        @error('fecha') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="flex items-center gap-2">
        <input type="checkbox" name="critico" value="1" class="w-4 h-4" @checked(old('critico'))>
        <label class="text-sm text-gray-700">Marcar como evento crítico</label>
    </div>
    <button type="submit" class="text-white bg-indigo-600 hover:bg-indigo-700 font-medium rounded-lg text-sm px-5 py-2.5">Guardar</button>
</form>
@endsection