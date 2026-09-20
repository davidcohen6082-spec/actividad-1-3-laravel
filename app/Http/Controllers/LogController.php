<?php

namespace App\Http\Controllers;

use App\Models\Log as LogModel;
use App\Models\Usuario;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index()
    {
        $logs = LogModel::with('usuario')->latest('fecha')->paginate(5);

        return view('admin.logs.index', compact('logs'));
    }

    public function create()
    {
        $usuarios = Usuario::orderBy('nombre')->get();

        return view('admin.logs.form', compact('usuarios'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'usuario_id' => ['required', 'exists:usuarios,id'],
            'accion' => ['required', 'string', 'min:5', 'max:150'],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
        ], [
            'usuario_id.required' => 'Selecciona un usuario.',
            'usuario_id.exists' => 'El usuario seleccionado no es válido.',
            'accion.required' => 'Describe la acción realizada.',
            'accion.min' => 'La descripción debe tener al menos 5 caracteres.',
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date' => 'Ingresa una fecha válida.',
            'fecha.before_or_equal' => 'La fecha no puede ser futura.',
        ]);

        LogModel::create([
            'usuario_id' => $validated['usuario_id'],
            'accion' => $validated['accion'],
            'fecha' => $validated['fecha'],
            'critico' => $request->boolean('critico'),
        ]);

        return redirect()->route('admin.logs.index')->with('success', 'Evento registrado correctamente.');
    }
}