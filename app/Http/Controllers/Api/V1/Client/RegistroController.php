<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Api\V1\Concerns\RespuestasApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\ClientResourceRequest;
use App\Http\Resources\Client\RegistroClienteResource;
use App\Models\Orden;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * /api/v1/client/mis-registros · registros (pedidos) del cliente autenticado.
 *
 * Doble barrera de propiedad de datos:
 *  1) Eloquent Scope  -> Orden::propios(auth()->id()): las listas solo traen filas propias.
 *  2) Policy          -> OrdenPolicy compara $usuario->id === $orden->usuario_id en cada acción
 *                        sobre un registro concreto (ver, modificar, cancelar) => 403 si es ajeno.
 */
class RegistroController extends Controller
{
    use RespuestasApi;

    private function consulta()
    {
        return Orden::with(['detalles.producto' => fn ($q) => $q->withTrashed()->select('id', 'nombre')]);
    }

    /** Lista EXCLUSIVA del cliente autenticado (filtro en la base de datos). */
    public function index(Request $request)
    {
        $request->validate(['estado' => ['nullable', 'in:'.implode(',', Orden::ESTADOS)]]);

        $registros = $this->consulta()
            ->propios($request->user()->id)
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->query('estado')))
            ->orderByDesc('fecha')->orderByDesc('id')
            ->paginate($this->porPagina($request))
            ->withQueryString();

        return RegistroClienteResource::collection($registros);
    }

    /** El propietario SIEMPRE es el usuario autenticado; nunca un valor del payload. */
    public function store(ClientResourceRequest $request)
    {
        $registro = Orden::registrarPara(
            $request->user(),
            collect($request->validated('items'))->values(),
            $request->validated('notas')
        );

        return $this->respuesta($registro->id, 'Registro creado correctamente.', 201);
    }

    public function show(Request $request, $id)
    {
        $registro = $this->buscar($id);

        if (! $registro) {
            return $this->error('El registro solicitado no existe.', 404);
        }

        $this->permitirSobre($request, 'view', $registro, 'No tienes autorización para consultar un registro que pertenece a otro cliente.');

        return new RegistroClienteResource($registro);
    }

    /** PUT/PATCH: la Policy 'update' se evalúa en ClientResourceRequest::authorize(). */
    public function update(ClientResourceRequest $request, $id)
    {
        $registro = $request->registro();

        if (! $registro->esCancelable()) {
            return $this->error("Solo se puede modificar un registro pendiente (estado actual: {$registro->estado}).", 409);
        }

        $registro->fill($request->safe()->only(['notas']))->save();

        return $this->respuesta($registro->id, 'Registro actualizado correctamente.');
    }

    /** DELETE: cancela el registro propio (devuelve el stock). Policy 'delete': dueño + cancelable. */
    public function destroy(Request $request, $id)
    {
        $registro = Orden::with('detalles')->find(ctype_digit((string) $id) ? $id : 0);

        if (! $registro) {
            return $this->error('El registro solicitado no existe.', 404);
        }

        $respuesta = Gate::forUser($request->user())->inspect('delete', $registro);
        abort_unless($respuesta->allowed(), 403, $respuesta->message());

        $registro->cancelar();

        return $this->respuesta($registro->id, 'Registro cancelado correctamente.');
    }

    private function buscar($id): ?Orden
    {
        return ctype_digit((string) $id) ? $this->consulta()->find($id) : null;
    }

    private function respuesta(int $id, string $mensaje, int $estado = 200): JsonResponse
    {
        return (new RegistroClienteResource($this->consulta()->find($id)))
            ->additional(['message' => $mensaje])->response()->setStatusCode($estado);
    }
}
