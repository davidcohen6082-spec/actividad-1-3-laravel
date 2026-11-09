<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Concerns\RespuestasApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminResourceRequest;
use App\Http\Resources\Admin\RecursoAdminResource;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * /api/v1/admin/recursos  ·  CRUD administrativo del recurso principal del sistema (artículos).
 *
 * - Autorización: Policy ProductoAdminPolicy (Opción A), complementada por el middleware solo.admin.
 * - Validación: FormRequest AdminResourceRequest.
 * - Salida: JsonResource RecursoAdminResource.
 * - "Inactivo" = borrado lógico (SoftDeletes). El listado SIEMPRE incluye los inactivos.
 */
class RecursoAdminController extends Controller
{
    use RespuestasApi;

    public function index(Request $request)
    {
        $this->permitirSobre($request, 'viewAny', Producto::class);

        $consulta = Producto::withTrashed()->with(['categoria', 'usuario']);

        match ($request->query('estado')) {
            'activos' => $consulta->whereNull('deleted_at'),
            'inactivos' => $consulta->whereNotNull('deleted_at'),
            default => null, // "todos": incluye registros inactivados
        };

        $buscar = trim((string) $request->query('buscar', ''));
        if ($buscar !== '') {
            $consulta->where(fn ($q) => $q->where('nombre', 'like', "%{$buscar}%")->orWhere('descripcion', 'like', "%{$buscar}%"));
        }

        foreach (['categoria_id', 'condicion'] as $filtro) {
            if ($request->filled($filtro)) {
                $consulta->where($filtro, $request->query($filtro));
            }
        }

        $ordenar = (string) $request->query('ordenar', 'id');
        $columna = ltrim($ordenar, '-');
        if (! in_array($columna, ['id', 'nombre', 'precio', 'stock'], true)) {
            [$ordenar, $columna] = ['id', 'id'];
        }
        $consulta->orderBy($columna, str_starts_with($ordenar, '-') ? 'desc' : 'asc');

        return RecursoAdminResource::collection($consulta->paginate($this->porPagina($request))->withQueryString());
    }

    public function show(Request $request, $id)
    {
        $this->permitirSobre($request, 'view', Producto::class);

        $recurso = ctype_digit((string) $id) ? Producto::withTrashed()->with(['categoria', 'usuario'])->find($id) : null;

        return $recurso ? new RecursoAdminResource($recurso) : $this->error('El recurso solicitado no existe.', 404);
    }

    public function store(AdminResourceRequest $request)
    {
        // Solo los campos validados llegan a la base de datos (protección contra asignación masiva).
        $datos = Arr::except($request->validated(), ['imagen', 'activo']);

        // El dueño nunca viene del cliente: se asigna igual que en el panel web.
        $datos['usuario_id'] = Usuario::whereHas('rol', fn ($q) => $q->where('nombre', 'administrador'))->value('id')
            ?? Usuario::value('id');

        $recurso = Producto::create($datos);
        $this->guardarImagen($request, $recurso);

        if ($request->has('activo') && ! $request->boolean('activo')) {
            $recurso->delete();
        }

        $this->auditar($request, 'recurso.creado', $recurso->id);

        return $this->respuesta($recurso->id, 'Recurso creado correctamente.', 201);
    }

    /** PUT reemplaza (todos los campos obligatorios); PATCH actualiza parcialmente. */
    public function update(AdminResourceRequest $request, $id)
    {
        $recurso = $request->recurso();
        $validados = $request->validated();

        $recurso->fill(Arr::except($validados, ['imagen', 'activo']))->save();
        $this->guardarImagen($request, $recurso);

        if (array_key_exists('activo', $validados)) {
            if (! $validados['activo'] && ! $recurso->trashed()) {
                $recurso->delete();
            } elseif ($validados['activo'] && $recurso->trashed()) {
                $recurso->restore();
            }
        }

        $this->auditar($request, 'recurso.actualizado', $recurso->id);

        return $this->respuesta($recurso->id, 'Recurso actualizado correctamente.');
    }

    /**
     * Borrado lógico por defecto (el recurso queda inactivo).
     * Con ?definitivo=true se elimina físicamente (solo si nada lo referencia).
     */
    public function destroy(Request $request, $id)
    {
        $definitivo = $request->boolean('definitivo');
        $this->permitirSobre($request, $definitivo ? 'forceDelete' : 'delete', Producto::class);

        $recurso = ctype_digit((string) $id) ? Producto::withTrashed()->find($id) : null;

        if (! $recurso) {
            return $this->error('El recurso solicitado no existe.', 404);
        }

        if ($definitivo) {
            if ($recurso->carritoItems()->exists() || $recurso->listaDeseos()->exists() || $recurso->ordenDetalles()->exists()) {
                return $this->error('No se puede eliminar definitivamente: el recurso está referenciado en carritos, listas de deseos u órdenes.', 409);
            }

            if ($recurso->imagen && file_exists(public_path($recurso->imagen))) {
                unlink(public_path($recurso->imagen));
            }

            $recurso->forceDelete();
            $this->auditar($request, 'recurso.eliminado_definitivamente', $recurso->id);

            return $this->ok(['id' => (int) $id, 'eliminacion' => 'definitiva'], 'Recurso eliminado definitivamente.');
        }

        if ($recurso->trashed()) {
            return $this->error('El recurso ya se encuentra inactivo.', 409);
        }

        $recurso->delete();
        $this->auditar($request, 'recurso.desactivado', $recurso->id);

        return $this->ok(['id' => (int) $id, 'eliminacion' => 'logica'], 'Recurso desactivado correctamente (borrado lógico).');
    }

    private function respuesta(int $id, string $mensaje, int $estado = 200): JsonResponse
    {
        $recurso = Producto::withTrashed()->with(['categoria', 'usuario'])->find($id);

        return (new RecursoAdminResource($recurso))->additional(['message' => $mensaje])->response()->setStatusCode($estado);
    }

    private function guardarImagen(Request $request, Producto $recurso): void
    {
        if (! $request->hasFile('imagen')) {
            return;
        }

        if ($recurso->imagen && file_exists(public_path($recurso->imagen))) {
            unlink(public_path($recurso->imagen));
        }

        $extension = $request->file('imagen')->extension();
        $nombre = "Producto_{$recurso->id}_1.{$extension}";
        $request->file('imagen')->move(public_path('images/productos'), $nombre);

        $recurso->imagen = 'images/productos/'.$nombre;
        $recurso->save();
    }

    /** Bitácora de auditoría de las operaciones de escritura (storage/logs/laravel.log). */
    private function auditar(Request $request, string $accion, int $id): void
    {
        Log::info('[AUDITORIA ADMIN] '.$accion, [
            'recurso_id' => $id,
            'administrador_id' => $request->user()->id,
            'usuario' => $request->user()->usuario,
            'ip' => $request->ip(),
        ]);
    }
}
