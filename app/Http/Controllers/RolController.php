<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RolController extends Controller
{
    public function index()
    {
        $roles = Rol::withCount('usuarios')->paginate(5);
        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        return view('admin.roles.form', ['rol' => null]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'min:3', 'max:50', 'unique:roles,nombre'],
        ], $this->mensajes());

        Rol::create($validated);

        return redirect()->route('admin.roles.index')->with('success', 'Rol creado correctamente.');
    }

    public function show($id)
    {
        $rol = Rol::withCount('usuarios')->find($id);

        if (!$rol) {
            return redirect()->route('admin.roles.index')->with('error', 'El rol solicitado no existe.');
        }

        return view('admin.roles.show', compact('rol'));
    }

    public function edit($id)
    {
        $rol = Rol::find($id);

        if (!$rol) {
            return redirect()->route('admin.roles.index')->with('error', 'El rol solicitado no existe.');
        }

        return view('admin.roles.form', compact('rol'));
    }

    public function update(Request $request, $id)
    {
        $rol = Rol::find($id);

        if (!$rol) {
            return redirect()->route('admin.roles.index')->with('error', 'El rol solicitado no existe.');
        }

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'min:3', 'max:50', Rule::unique('roles', 'nombre')->ignore($rol->id)],
        ], $this->mensajes());

        $rol->update($validated);

        return redirect()->route('admin.roles.index')->with('success', 'Rol actualizado correctamente.');
    }

    // Borrado lógico
    public function destroy($id)
    {
        $rol = Rol::find($id);

        if (!$rol) {
            return redirect()->route('admin.roles.index')->with('error', 'El rol solicitado no existe.');
        }

        $rol->delete();

        return redirect()->route('admin.roles.index')->with('success', 'Rol eliminado. Puedes restaurarlo desde la papelera.');
    }

    public function papelera()
    {
        $roles = Rol::onlyTrashed()->withCount('usuarios')->paginate(5);
        return view('admin.roles.papelera', compact('roles'));
    }

    public function restaurar($id)
    {
        $rol = Rol::onlyTrashed()->find($id);

        if (!$rol) {
            return redirect()->route('admin.roles.papelera')->with('error', 'El rol no existe o no está eliminado.');
        }

        $rol->restore();

        return redirect()->route('admin.roles.papelera')->with('success', 'Rol restaurado correctamente.');
    }

    // Borrado físico (solo si ya está en la papelera)
    public function forceDestroy($id)
    {
        $rol = Rol::onlyTrashed()->withCount('usuarios')->find($id);

        if (!$rol) {
            return redirect()->route('admin.roles.papelera')->with('error', 'El rol no existe o no está en la papelera.');
        }

        if ($rol->usuarios_count > 0) {
            return redirect()->route('admin.roles.papelera')->with('error', 'No se puede eliminar definitivamente: hay usuarios asociados a este rol.');
        }

        $rol->forceDelete();

        return redirect()->route('admin.roles.papelera')->with('success', 'Rol eliminado definitivamente.');
    }

    private function mensajes(): array
    {
        return [
            'nombre.required' => 'El nombre del rol es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'nombre.max' => 'El nombre no puede superar los 50 caracteres.',
            'nombre.unique' => 'Ya existe un rol con ese nombre.',
        ];
    }
}