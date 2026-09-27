@extends('layout.admin')
@section('titulo', 'Papelera de Logs')
@section('contenido')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Papelera — Logs</h1>
    <a href="{{ route('admin.logs.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; Volver al listado</a>
</div>
<div class="relative overflow-x-auto shadow-sm rounded-lg">
    <table class="w-full text-sm text-left text-gray-500">
        <thead class="text-xs text-gray-700 uppercase bg-gray-100">
            <tr>
                <th class="px-6 py-3">ID</th>
                <th class="px-6 py-3">Acción</th>
                <th class="px-6 py-3">Eliminado el</th>
                <th class="px-6 py-3">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                <tr class="bg-white border-b">
                    <td class="px-6 py-4">{{ $log->id }}</td>
                    <td class="px-6 py-4">{{ $log->accion }}</td>
                    <td class="px-6 py-4">{{ $log->deleted_at->format('Y-m-d H:i') }}</td>
                    <td class="px-6 py-4 space-x-2">
                        <form action="{{ route('admin.logs.restaurar', $log->id) }}" method="POST" class="inline-block"
                            onsubmit="return confirm('¿Restaurar este evento?');">
                            @csrf @method('PUT')
                            <button type="submit" class="font-medium text-green-600 hover:underline bg-transparent border-0 p-0 cursor-pointer">Restaurar</button>
                        </form>
                        <form action="{{ route('admin.logs.forceDestroy', $log->id) }}" method="POST" class="inline-block"
                            onsubmit="return confirm('Esta acción es permanente. ¿Eliminar definitivamente este evento?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="font-medium text-red-600 hover:underline bg-transparent border-0 p-0 cursor-pointer">Eliminar definitivamente</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr class="bg-white border-b"><td class="px-6 py-4" colspan="4">No hay eventos eliminados.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $logs->links() }}</div>
@endsection