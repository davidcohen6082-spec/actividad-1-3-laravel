<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Orden;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsuarioController extends RecursoController
{
    protected function modelo(): string
    {
        return Usuario::class;
    }

    protected function etiqueta(): string
    {
        return 'usuario';
    }

    protected function relaciones(): array
    {
        return ['rol'];
    }

    protected function buscables(): array
    {
        return ['nombre', 'email'];
    }

    protected function filtros(): array
    {
        return ['rol_id'];
    }

    protected function ordenables(): array
    {
        return ['id', 'nombre', 'email', 'fecha_registro'];
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'nombre' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('usuarios', 'email')->ignore($registro?->id)],
            'rol_id' => ['required', Rule::exists('roles', 'id')->whereNull('deleted_at')],
            'proveedor_social' => ['nullable', 'in:google,facebook'],
            'password' => $registro ? ['nullable', 'string', 'min:8', 'max:100'] : ['required', 'string', 'min:8', 'max:100'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    protected function mensajes(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.unique' => 'Ese correo ya está registrado.',
            'rol_id.required' => 'Selecciona un rol.',
            'rol_id.exists' => 'El rol seleccionado no es válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'foto.image' => 'El archivo debe ser una imagen.',
            'foto.mimes' => 'La imagen debe ser jpg, jpeg, png o webp.',
            'foto.max' => 'La imagen no debe superar los 2 MB.',
        ];
    }

    protected function atributos(array $validados, ?Model $registro): array
    {
        unset($validados['foto']);

        if (! empty($validados['password'])) {
            $validados['password_hash'] = Hash::make($validados['password']);
        }
        unset($validados['password']);

        if ($registro === null) {
            $validados['fecha_registro'] = now();
        }

        return $validados;
    }

    protected function despuesDeGuardar(Request $request, Model $registro): void
    {
        if (! $request->hasFile('foto')) {
            return;
        }

        if ($registro->foto && file_exists(public_path($registro->foto))) {
            unlink(public_path($registro->foto));
        }

        $extension = $request->file('foto')->getClientOriginalExtension();
        $nombreArchivo = "Usuario_{$registro->id}_1.{$extension}";
        $request->file('foto')->move(public_path('images/usuarios'), $nombreArchivo);

        $registro->foto = 'images/usuarios/'.$nombreArchivo;
        $registro->save();
    }

    protected function motivoBloqueoEliminar(Model $registro, bool $definitivo): ?string
    {
        if ($definitivo
            && (Producto::withTrashed()->where('usuario_id', $registro->id)->exists()
                || Orden::where('usuario_id', $registro->id)->exists())) {
            return 'No se puede eliminar definitivamente: el usuario tiene productos u órdenes asociadas.';
        }

        return null;
    }

    protected function alEliminarDefinitivamente(Model $registro): void
    {
        if ($registro->foto && file_exists(public_path($registro->foto))) {
            unlink(public_path($registro->foto));
        }
    }
}
