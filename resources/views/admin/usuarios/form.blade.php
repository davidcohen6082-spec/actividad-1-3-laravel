@extends('layout.admin')
@section('titulo', 'Formulario de Usuario')
@section('contenido')
<h1 class="text-2xl font-bold mb-6">Nuevo Usuario</h1>
<form action="{{ route('admin.usuarios.store') }}" method="POST" enctype="multipart/form-data"
    class="max-w-lg bg-white p-6 rounded-lg border border-gray-200 shadow-sm space-y-4" id="form-usuario">
    @csrf
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-900">Nombre completo</label>
        <input type="text" name="nombre" value="{{ old('nombre') }}" required minlength="3" maxlength="100"
            class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5 @error('nombre') border-red-500 @enderror"
            placeholder="Ej. Ana Pérez">
        @error('nombre') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-900">Correo electrónico</label>
        <input type="email" name="email" value="{{ old('email') }}" required maxlength="150"
            class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5 @error('email') border-red-500 @enderror"
            placeholder="correo@ejemplo.com">
        @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-900">Rol</label>
        <select name="rol_id" required
            class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5 @error('rol_id') border-red-500 @enderror">
            <option value="">-- Selecciona un rol --</option>
            @foreach ($roles as $rol)
                <option value="{{ $rol->id }}" @selected(old('rol_id') == $rol->id)>{{ ucfirst($rol->nombre) }}</option>
            @endforeach
        </select>
        @error('rol_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <span class="block mb-2 text-sm font-medium text-gray-900">Tipo de inicio de sesión</span>
        <div class="flex items-center gap-4 flex-wrap">
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="radio" name="proveedor_social" value="" class="w-4 h-4" @checked(old('proveedor_social', '') == '')> Local
            </label>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="radio" name="proveedor_social" value="google" class="w-4 h-4" @checked(old('proveedor_social') == 'google')> Google
            </label>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="radio" name="proveedor_social" value="facebook" class="w-4 h-4" @checked(old('proveedor_social') == 'facebook')> Facebook
            </label>
        </div>
    </div>
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-900">Foto de perfil</label>
        <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp"
            class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 @error('foto') border-red-500 @enderror">
        <p class="mt-1 text-xs text-gray-500">Formatos permitidos: jpg, jpeg, png, webp. Máximo 2 MB.</p>
        @error('foto') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <button type="submit" class="text-white bg-indigo-600 hover:bg-indigo-700 font-medium rounded-lg text-sm px-5 py-2.5">Guardar</button>
</form>

<script>
document.getElementById('form-usuario').addEventListener('submit', function (e) {
    const foto = document.querySelector('input[name="foto"]');
    if (foto.files.length > 0) {
        const maxSize = 2 * 1024 * 1024;
        const tiposValidos = ['image/jpeg', 'image/png', 'image/webp'];
        if (!tiposValidos.includes(foto.files[0].type)) {
            alert('La foto debe ser un archivo JPG, PNG o WEBP.');
            e.preventDefault();
            return;
        }
        if (foto.files[0].size > maxSize) {
            alert('La foto no debe superar los 2 MB.');
            e.preventDefault();
        }
    }
});
</script>
@endsection