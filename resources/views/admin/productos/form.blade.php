@extends('layout.admin')
@section('titulo', $producto ? 'Editar Producto' : 'Nuevo Producto')
@section('contenido')
<h1 class="text-2xl font-bold mb-6">{{ $producto ? 'Editar Producto' : 'Nuevo Producto' }}</h1>
<form action="{{ $producto ? route('admin.productos.update', $producto->id) : route('admin.productos.store') }}" method="POST"
    enctype="multipart/form-data" class="max-w-lg bg-white p-6 rounded-lg border border-gray-200 shadow-sm space-y-4" id="form-producto">
    @csrf
    @if ($producto) @method('PUT') @endif
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-900">Nombre</label>
        <input type="text" name="nombre" value="{{ old('nombre', $producto->nombre ?? '') }}" required minlength="3" maxlength="150"
            class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5 @error('nombre') border-red-500 @enderror"
            placeholder="Ej. Camisa azul talla M">
        @error('nombre') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-900">Descripción</label>
        <textarea name="descripcion" rows="3" maxlength="1000"
            class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5 @error('descripcion') border-red-500 @enderror"
            placeholder="Detalles del artículo...">{{ old('descripcion', $producto->descripcion ?? '') }}</textarea>
        @error('descripcion') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-900">Talla</label>
        <input type="text" name="talla" value="{{ old('talla', $producto->talla ?? '') }}" maxlength="20"
            class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5" placeholder="Ej. M">
    </div>
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-900">Categoría</label>
        <select name="categoria_id" required
            class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5 @error('categoria_id') border-red-500 @enderror">
            <option value="">-- Selecciona una categoría --</option>
            @foreach ($categorias as $categoria)
                <option value="{{ $categoria->id }}" @selected(old('categoria_id', $producto->categoria_id ?? '') == $categoria->id)>{{ $categoria->nombre }}</option>
            @endforeach
        </select>
        @error('categoria_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <span class="block mb-2 text-sm font-medium text-gray-900">Condición</span>
        @php $condicionActual = old('condicion', $producto->condicion ?? ''); @endphp
        <div class="flex items-center gap-4">
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="radio" name="condicion" value="nuevo" required @checked($condicionActual == 'nuevo')> Nuevo
            </label>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="radio" name="condicion" value="usado" @checked($condicionActual == 'usado')> Usado
            </label>
        </div>
        @error('condicion') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block mb-2 text-sm font-medium text-gray-900">Precio</label>
            <input type="number" name="precio" value="{{ old('precio', $producto->precio ?? '') }}" step="0.01" min="0" max="99999.99" required
                class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5 @error('precio') border-red-500 @enderror"
                placeholder="0.00">
            @error('precio') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block mb-2 text-sm font-medium text-gray-900">Stock</label>
            <input type="number" name="stock" value="{{ old('stock', $producto->stock ?? '') }}" min="0" max="1000" required
                class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5 @error('stock') border-red-500 @enderror"
                placeholder="1">
            @error('stock') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-900">Imagen del artículo</label>
        @if (($producto->imagen ?? null))
            <div class="mb-2 flex items-center gap-3">
                <img src="{{ asset($producto->imagen) }}" class="w-16 h-16 rounded object-cover border border-gray-200" alt="Imagen actual">
                <span class="text-xs text-gray-500">Imagen actual. Sube una nueva solo si quieres reemplazarla.</span>
            </div>
        @endif
        <input type="file" name="imagen" accept=".jpg,.jpeg,.png,.webp"
            class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 @error('imagen') border-red-500 @enderror">
        <p class="mt-1 text-xs text-gray-500">Formatos permitidos: jpg, jpeg, png, webp. Máximo 2 MB.</p>
        @error('imagen') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <button type="submit" class="text-white bg-indigo-600 hover:bg-indigo-700 font-medium rounded-lg text-sm px-5 py-2.5">Guardar</button>
</form>

<script>
document.getElementById('form-producto').addEventListener('submit', function (e) {
    const imagen = document.querySelector('input[name="imagen"]');
    if (imagen.files.length > 0) {
        const maxSize = 2 * 1024 * 1024;
        const tiposValidos = ['image/jpeg', 'image/png', 'image/webp'];
        if (!tiposValidos.includes(imagen.files[0].type)) {
            alert('La imagen debe ser un archivo JPG, PNG o WEBP.');
            e.preventDefault();
            return;
        }
        if (imagen.files[0].size > maxSize) {
            alert('La imagen no debe superar los 2 MB.');
            e.preventDefault();
        }
    }
});
</script>
@endsection