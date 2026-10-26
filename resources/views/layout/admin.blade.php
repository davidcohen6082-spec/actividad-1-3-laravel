<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', 'Panel Administrativo')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">

    {{-- ENCABEZADO --}}
    <nav class="bg-white border-b border-gray-200 fixed z-30 w-full">
        <div class="px-3 py-3 lg:px-5">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <button data-drawer-target="sidebar" data-drawer-toggle="sidebar" aria-controls="sidebar" type="button"
                        class="inline-flex items-center p-2 text-sm text-gray-500 rounded-lg lg:hidden hover:bg-gray-100">
                        <span class="sr-only">Abrir menú</span>
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <a href="{{ route('admin.dashboard') }}" class="flex ms-2">
                        <span class="self-center text-lg sm:text-xl font-semibold whitespace-nowrap">Bancos de Ropa · Admin</span>
                    </a>
                </div>
                <div class="flex items-center gap-4">
                    @auth('admin')
                        <span class="text-sm text-gray-700">{{ Auth::guard('admin')->user()->nombre_completo }}</span>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="text-sm text-red-600 hover:underline">Cerrar sesión</button>
                        </form>
                        <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center font-semibold">
                            {{ strtoupper(substr(Auth::guard('admin')->user()->nombre_completo, 0, 1)) }}
                        </div>
                    @endauth

                    @guest('admin')
                        <a href="{{ route('admin.login') }}" class="text-sm text-blue-600 hover:underline">Iniciar sesión</a>
                    @endguest
                </div>
            </div>
        </div>
    </nav>

    {{-- MENÚ PRINCIPAL DE NAVEGACIÓN --}}
    <aside id="sidebar" class="fixed top-0 left-0 z-20 w-64 h-screen pt-20 transition-transform -translate-x-full bg-white border-r border-gray-200 lg:translate-x-0" aria-label="Sidebar">
        <div class="h-full px-3 pb-4 overflow-y-auto">
            <ul class="space-y-1 font-medium">
                <li>
    <div class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-100 {{ request()->routeIs('admin.roles.*') ? 'bg-gray-100 font-semibold' : 'text-gray-900' }}">
        <a href="{{ route('admin.roles.index') }}"