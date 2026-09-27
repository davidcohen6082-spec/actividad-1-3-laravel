<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        return view('admin.usuarios.form', ['usuario' => null, 'roles' => $roles]);
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
        ], $this->mensajes());

        $usuario = Usuario::create([
            'rol_id' => $validated['rol_id'],
            'nombre' => $validated['nombre'],
            'email' => $validated['email'],
            'password_hash' => bcrypt('password'),
            'proveedor_social' => $validated['proveedor_social'],
            'fecha_registro' => now(),
        ]);

        $this->guardarFoto($request, $usuario);
        $usuario->save();

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario creado correctamente.');
    }

    public function show($id)
    {
        $usuario = Usuario::with('rol')->find($id);

        if (!$usuario) {
            return redirect()->route('admin.usuarios.index')->with('error', 'El usuario solicitado no existe.');
        }

        return view('admin.usuarios.show', compact('usuario'));
    }

    public function edit($id)
    {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return redirect()->route('admin.usuarios.index')->with('error', 'El usuario solicitado no existe.');
        }

        $roles = Rol::orderBy('nombre')->get();
        return view('admin.usuarios.form', compact('usuario', 'roles'));
    }

    public function update(Request $request, $id)
    {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return redirect()->route('admin.usuarios.index')->with('error', 'El usuario solicitado no existe.');
        }

        $request->merge(['proveedor_social' => $request->proveedor_social ?: null]);

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('usuarios', 'email')->ignore($usuario->id)],
            'rol_id' => ['required', 'exists:roles,id'],
            'proveedor_social' => ['nullable', 'in:google,facebook'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], $this->mensajes());

        $usuario->fill([
            'rol_id' => $validated['rol_id'],
            'nombre' => $validated['nombre'],
            'email' => $validated['email'],
            'proveedor_social' => $validated['proveedor_social'],
        ]);

        $this->guardarFoto($request, $usuario);
        $usuario->save();

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy($id)
    {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return redirect()->route('admin.usuarios.index')->with('error', 'El usuario solicitado no existe.');
        }

        $usuario->delete();

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario eliminado. Puedes restaurarlo desde la papelera.');
    }

    public function papelera()
    {
        $usuarios = Usuario::onlyTrashed()->with('rol')->paginate(5);
        return view('admin.usuarios.papelera', compact('usuarios'));
    }

    public function restaurar($id)
    {
        $usuario = Usuario::onlyTrashed()->find($id);

        if (!$usuario) {
            return redirect()->route('admin.usuarios.papelera')->with('error', 'El usuario no existe o no está eliminado.');
        }

        $usuario->restore();

        return redirect()->route('admin.usuarios.papelera')->with('success', 'Usuario restaurado correctamente.');
    }

    public function forceDestroy($id)
    {
        $usuario = Usuario::onlyTrashed()->find($id);

        if (!$usuario) {
            return redirect()->route('admin.usuarios.papelera')->with('error', 'El usuario no existe o no está en la papelera.');
        }

        $productos = $usuario->productos()->count();
        $ordenes = $usuario->ordenes()->count();

        if ($productos > 0 || $ordenes > 0) {
            return redirect()->route('admin.usuarios.papelera')->with('error', 'No se puede eliminar definitivamente: el usuario tiene productos u órdenes asociadas.');
        }

        if ($usuario->foto && file_exists(public_path($usuario->foto))) {
            unlink(public_path($usuario->foto));
        }

        $usuario->forceDelete();

        return redirect()->route('admin.usuarios.papelera')->with('success', 'Usuario eliminado definitivamente.');
    }

    private function guardarFoto(Request $request, Usuario $usuario): void
    {
        if (!$request->hasFile('foto')) {
            return;
        }

        if ($usuario->foto && file_exists(public_path($usuario->foto))) {
            unlink(public_path($usuario->foto));
        }

        $extension = $request->file('foto')->getClientOriginalExtension();
        $nombreArchivo = "Usuario_{$usuario->id}_1.{$extension}";
        $request->file('foto')->move(public_path('images/usuarios'), $nombreArchivo);

        $usuario->foto = 'images/usuarios/' . $nombreArchivo;
    }

    private function mensajes(): array
    {
        return [
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
        ];
    }
}