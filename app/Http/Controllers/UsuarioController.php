<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    public function index()
    {
        $usuarios = Usuario::with('rol')->paginate(5);

        return view('admin.usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        $roles = Rol::orderBy('nombre')->get();

        return view('admin.usuarios.form', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->merge(['proveedor_social' => $request->proveedor_social ?: null]);

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:usuarios,email'],
            'rol_id' => ['required', 'exists:roles,id'],
            'proveedor_social' => ['nullable', 'in:google,facebook'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.unique' => 'Ese correo ya está registrado.',
            'rol_id.required' => 'Selecciona un rol.',
            'rol_id.exists' => 'El rol seleccionado no es válido.',
            'foto.image' => 'El archivo debe ser una imagen.',
            'foto.mimes' => 'La imagen debe ser jpg, jpeg, png o webp.',
            'foto.max' => 'La imagen no debe superar los 2 MB.',
        ]);

        $usuario = Usuario::create([
            'rol_id' => $validated['rol_id'],
            'nombre' => $validated['nombre'],
            'email' => $validated['email'],
            'password_hash' => bcrypt('password'), // temporal, aun no implementamos login
            'proveedor_social' => $validated['proveedor_social'],
            'fecha_registro' => now(),
        ]);

        if ($request->hasFile('foto')) {
            $extension = $request->file('foto')->getClientOriginalExtension();
            $nombreArchivo = "Usuario_{$usuario->id}_1.{$extension}";
            $request->file('foto')->move(public_path('images/usuarios'), $nombreArchivo);

            $usuario->update(['foto' => 'images/usuarios/' . $nombreArchivo]);
        }

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario creado correctamente.');
    }
}