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
        return view('admin.logs.form', ['log' => null, 'usuarios' => $usuarios]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->reglas(), $this->mensajes());

        LogModel::create([
            'usuario_id' => $validated['usuario_id'],
            'accion' => $validated['accion'],
            'fecha' => $validated['fecha'],
            'critico' => $request->boolean('critico'),
        ]);

        return redirect()->route('admin.logs.index')->with('success', 'Evento registrado correctamente.');
    }

    public function show($id)
    {
        $log = LogModel::with('usuario')->find($id);

        if (!$log) {
            return redirect()->route('admin.logs.index')->with('error', 'El registro solicitado no existe.');
        }

        return view('admin.logs.show', compact('log'));
    }

    public function edit($id)
    {
        $log = LogModel::find($id);

        if (!$log) {
            return redirect()->route('admin.logs.index')->with('error', 'El registro solicitado no existe.');
        }

        $usuarios = Usuario::orderBy('nombre')->get();
        return view('admin.logs.form', compact('log', 'usuarios'));
    }

    public function update(Request $request, $id)
    {
        $log = LogModel::find($id);

        if (!$log) {
            return redirect()->route('admin.logs.index')->with('error', 'El registro solicitado no existe.');
        }

        $validated = $request->validate($this->reglas(), $this->mensajes());

        $log->update([
            'usuario_id' => $validated['usuario_id'],
            'accion' => $validated['accion'],
            'fecha' => $validated['fecha'],
            'critico' => $request->boolean('critico'),
        ]);

        return redirect()->route('admin.logs.index')->with('success', 'Evento actualizado correctamente.');
    }

    public function destroy($id)
    {
        $log = LogModel::find($id);

        if (!$log) {
            return redirect()->route('admin.logs.index')->with('error', 'El registro solicitado no existe.');
        }

        $log->delete();

        return redirect()->route('admin.logs.index')->with('success', 'Evento eliminado. Puedes restaurarlo desde la papelera.');
    }

    public function papelera()
    {
        $logs = LogModel::onlyTrashed()->with('usuario')->paginate(5);
        return view('admin.logs.papelera', compact('logs'));
    }

    public function restaurar($id)
    {
        $log = LogModel::onlyTrashed()->find($id);

        if (!$log) {
            return redirect()->route('admin.logs.papelera')->with('error', 'El registro no existe o no está eliminado.');
        }

        $log->restore();

        return redirect()->route('admin.logs.papelera')->with('success', 'Evento restaurado correctamente.');
    }

    public function forceDestroy($id)
    {
        $log = LogModel::onlyTrashed()->find($id);

        if (!$log) {
            return redirect()->route('admin.logs.papelera')->with('error', 'El registro no existe o no está en la papelera.');
        }

        $log->forceDelete();

        return redirect()->route('admin.logs.papelera')->with('success', 'Evento eliminado definitivamente.');
    }

    private function reglas(): array
    {
        return [
            'usuario_id' => ['required', 'exists:usuarios,id'],
            'accion' => ['required', 'string', 'min:5', 'max:150'],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    private function mensajes(): array
    {
        return [
            'usuario_id.required' => 'Selecciona un usuario.',
            'usuario_id.exists' => 'El usuario seleccionado no es válido.',
            'accion.required' => 'Describe la acción realizada.',
            'accion.min' => 'La descripción debe tener al menos 5 caracteres.',
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date' => 'Ingresa una fecha válida.',
            'fecha.before_or_equal' => 'La fecha no puede ser futura.',
        ];
    }
}