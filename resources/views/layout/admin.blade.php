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
                <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center font-semibold">A</div>
            </div>
        </div>
    </nav>

    {{-- MENÚ PRINCIPAL DE NAVEGACIÓN --}}
    <aside id="sidebar" class="fixed top-0 left-0 z-20 w-64 h-screen pt-20 transition-transform -translate-x-full bg-white border-r border-gray-200 lg:translate-x-0" aria-label="Sidebar">
        <div class="h-full px-3 pb-4 overflow-y-auto">
            <ul class="space-y-1 font-medium">
                <li>
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center p-2 rounded-lg hover:bg-gray-100 {{ request()->routeIs('admin.dashboard') ? 'bg-gray-100 font-semibold' : 'text-gray-900' }}">Inicio</a>
                </li>
                <li>
                    <a href="{{ route('admin.roles.index') }}" class="flex items-center p-2 rounded-lg hover:bg-gray-100 {{ request()->routeIs('admin.roles.*') ? 'bg-gray-100 font-semibold' : 'text-gray-900' }}">Roles</a>
                </li>
                <li>
                    <a href="{{ route('admin.usuarios.index') }}" class="flex items-center p-2 rounded-lg hover:bg-gray-100 {{ request()->routeIs('admin.usuarios.*') ? 'bg-gray-100 font-semibold' : 'text-gray-900' }}">Usuarios</a>
                </li>
                <li>
                    <a href="{{ route('admin.productos.index') }}" class="flex items-center p-2 rounded-lg hover:bg-gray-100 {{ request()->routeIs('admin.productos.*') ? 'bg-gray-100 font-semibold' : 'text-gray-900' }}">Productos</a>
                </li>
                <li>
                    <a href="{{ route('admin.logs.index') }}" class="flex items-center p-2 rounded-lg hover:bg-gray-100 {{ request()->routeIs('admin.logs.*') ? 'bg-gray-100 font-semibold' : 'text-gray-900' }}">Logs / Métricas</a>
                </li>
                <li class="pt-3 mt-3 border-t border-gray-200">
                    <a href="{{ route('admin.contacto') }}" class="flex items-center p-2 rounded-lg hover:bg-gray-100 {{ request()->routeIs('admin.contacto') ? 'bg-gray-100 font-semibold' : 'text-gray-900' }}">Contacto</a>
                </li>
            </ul>
        </div>
    </aside>

    {{-- ÁREA DE CONTENIDO --}}
    <div class="p-4 lg:ml-64 pt-24">
        @yield('contenido')

        {{-- PIE DE PÁGINA --}}
        <footer class="mt-8 py-4 border-t border-gray-200 text-center text-sm text-gray-500">
            © {{ date('Y') }} Sistema de Bancos de Ropa — Panel Administrativo
        </footer>
    </div>

</body>
</html>