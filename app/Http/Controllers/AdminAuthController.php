<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function mostrarLogin()
    {
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credenciales = $request->validate([
            'usuario' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'usuario.required' => 'El usuario es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        $administrador = \App\Models\Administrador::where('usuario', $credenciales['usuario'])->first();

        if (! $administrador || ! \Illuminate\Support\Facades\Hash::check($credenciales['password'], $administrador->password)) {
            return back()
                ->withErrors(['usuario' => 'Usuario o contraseña incorrectos.'])
                ->onlyInput('usuario');
        }

        if (! $administrador->activo) {
            return back()
                ->withErrors(['usuario' => 'Esta cuenta se encuentra deshabilitada.'])
                ->onlyInput('usuario');
        }

        Auth::guard('admin')->login($administrador);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard')->with('success', 'Inicio de sesión exitoso.');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'Sesión cerrada correctamente.');
    }
}