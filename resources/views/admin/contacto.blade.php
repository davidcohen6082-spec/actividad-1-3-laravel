@extends('layout.admin')
@section('titulo', 'Contacto')
@section('contenido')
<h1 class="text-2xl font-bold mb-6">Contacto</h1>
<div class="max-w-lg bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
    <p class="mb-4 text-gray-600">¿Dudas o soporte sobre el sistema? Escríbenos:</p>
    <ul class="space-y-2 text-gray-700">
        <li><strong>Correo:</strong> soporte@bancoderopa.test</li>
        <li><strong>Teléfono:</strong> +52 33 0000 0000</li>
        <li><strong>Horario:</strong> Lunes a viernes, 9:00–18:00</li>
    </ul>
</div>
@endsection