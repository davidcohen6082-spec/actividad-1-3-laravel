<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar sesión — Administración</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 h-screen flex items-center justify-center">
    <div class="w-full max-w-sm bg-white p-8 rounded-lg shadow">
        <h1 class="text-xl font-bold mb-6 text-center">Acceso administrativo</h1>

        @if (session('success'))
            <div class="mb-4 p-3 text-sm text-green-800 bg-green-50 border border-green-300 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-3 text-sm text-red-800 bg-red-50 border border-red-300 rounded-lg">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Usuario</label>
                <input type="text" name="usuario" value="{{ old('usuario') }}" required
                       class="w-full border rounded-lg p-2">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Contraseña</label>
                <input type="password" name="password" required
                       class="w-full border rounded-lg p-2">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white rounded-lg p-2 font-medium">
                Iniciar sesión
            </button>
        </form>
    </div>
</body>
</html>