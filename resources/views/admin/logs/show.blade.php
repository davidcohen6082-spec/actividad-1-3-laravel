@extends('layout.admin')
@section('titulo', 'Detalle del Evento')
@section('contenido')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Detalle del Evento</h1>
    <a href="{{ route('admin.logs.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; Volver al listado</a>
</div>
<div class="max-w-lg bg-white p-6 rounded-lg border border-gray-200 shadow-sm space-y-3">
    <div><span class="font-medium text-gray-700">ID:</span> {{ $log->id }}</div>
    <div><span class="font-medium text-gray-700">Usuario:</span> {{ $log->usuario->nombre ?? 'Desconocido' }}</div>
    <div><span class="font-medium text-gray-700">Acción:</span> {{ $log->accion }}</div>
    <div><span class="font-medium text-gray-700">Fecha:</span> {{ \Illuminate\Support\Carbon::parse($log->fecha)->format('Y-m-d') }}</div>
    <div><span class="font-medium text-gray-700">Crítico:</span> {{ $log->critico ? 'Sí' : 'No' }}</div>
</div>
@endsection