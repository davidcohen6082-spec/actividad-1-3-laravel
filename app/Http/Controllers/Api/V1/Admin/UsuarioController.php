<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\CambiarEstadoUsuarioRequest;
use App\Http\Resources\Admin\UsuarioAdminResource;
use App\Models\Administrador;
use App\Models\Orden;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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

    /**
     * GET /api/v1/admin/usuarios — listado general: clientes y administradores.
     * Filtros: ?tipo=todos|cliente|administrador, ?activo=true|false, ?buscar=, ?per_page=, ?page=
     */
    public function index(Request $request)
    {
        $this->permitir($request, 'ver-registros');

        $tipo = $request->query('tipo', 'todos');
        $buscar = trim((string) $request->query('buscar', ''));
        $activo = $request->has('activo') ? $request->boolean('activo') : null;
        $usuarios = collect();

        if (in_array($tipo, ['todos', 'cliente'], true)) {
            $usuarios = $usuarios->concat(
                Usuario::with('rol')
                    ->when($buscar !== '', fn ($q) => $q->where(fn ($d) => $d->where('nombre', 'like', "%{$buscar}%")->orWhere('email', 'like', "%{$buscar}%")))
                    ->when($activo !== null, fn ($q) => $q->where('activo', $activo))
                    ->get()
            );
        }

        if (in_array($tipo, ['todos', 'administrador'], true)) {
            $usuarios = $usuarios->concat(
                Administrador::query()
                    ->when($buscar !== '', fn ($q) => $q->where(fn ($d) => $d->where('nombre_completo', 'like', "%{$buscar}%")->orWhere('usuario', 'like', "%{$buscar}%")))
                    ->when($activo !== null, fn ($q) => $q->where('activo', $activo))
                    ->get()
            );
        }

        // Dos tablas distintas: se unen, se ordenan y se paginan en memoria (volumen pequeño).
        $usuarios = $usuarios->sortBy(fn ($u) => mb_strtolower($u->nombre ?? $u->nombre_completo))->values();
        $porPagina = $this->porPagina($request);
        $pagina = max(1, (int) $request->query('page', 1));

        $paginador = new LengthAwarePaginator(
            $usuarios->forPage($pagina, $porPagina)->values(),
            $usuarios->count(),
            $porPagina,
            $pagina,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return UsuarioAdminResource::collection($paginador);
    }

    /**
     * PATCH /api/v1/admin/usuarios/{id}/estado — activa/desactiva una cuenta y/o cambia su rol (cliente)
     * o nivel (administrador). Al desactivar se revocan todos sus tokens Sanctum.
     */
    public function estado(CambiarEstadoUsuarioRequest $request, $id)
    {
        $datos = $request->validated();
        $esAdmin = $request->tipo() === 'administrador';

        $cuenta = ctype_digit((string) $id)
            ? ($esAdmin ? Administrador::find($id) : Usuario::find($id))
            : null;

        if (! $cuenta) {
            return $this->error('El usuario solicitado no existe.', 404);
        }

        // Protección contra elevación de privilegios / bloqueo accidental.
        if ($esAdmin && $cuenta->is($request->user())) {
            return $this->error('No puedes cambiar el estado ni el nivel de tu propia cuenta.', 403);
        }

        $cambios = array_intersect_key($datos, array_flip(['activo', 'rol_id', 'nivel']));
        $cuenta->forceFill($cambios)->save();

        if (array_key_exists('activo', $cambios) && ! $cambios['activo']) {
            $cuenta->tokens()->delete();
        }

        Log::info('[AUDITORIA ADMIN] usuario.estado', [
            'tipo' => $request->tipo(),
            'usuario_afectado_id' => $cuenta->id,
            'cambios' => $cambios,
            'administrador_id' => $request->user()->id,
            'ip' => $request->ip(),
        ]);

        return (new UsuarioAdminResource($cuenta->load($esAdmin ? [] : ['rol'])))
            ->additional(['message' => 'Estado de la cuenta actualizado correctamente.']);
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
