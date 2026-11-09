<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Concerns\RespuestasApi;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;

/**
 * CRUD administrativo reutilizable. Cada recurso (roles, usuarios, productos...) solo
 * declara sus particularidades: modelo, reglas de validación, filtros y restricciones.
 * Así las mismas reglas de autorización y el mismo formato de respuesta se aplican
 * de manera uniforme a todos los endpoints del área administrativa.
 */
abstract class RecursoController extends Controller
{
    use RespuestasApi;

    abstract protected function modelo(): string;

    abstract protected function etiqueta(): string;

    abstract protected function reglas(?Model $registro): array;

    protected function mensajes(): array
    {
        return [];
    }

    protected function relaciones(): array
    {
        return [];
    }

    protected function conteos(): array
    {
        return [];
    }

    /** Columnas donde busca el parámetro ?buscar= */
    protected function buscables(): array
    {
        return [];
    }

    /** Columnas filtrables por igualdad, p. ej. ?categoria_id=3 */
    protected function filtros(): array
    {
        return [];
    }

    /** Columnas permitidas en ?ordenar= (prefijo "-" para descendente) */
    protected function ordenables(): array
    {
        return ['id'];
    }

    protected function ordenPredeterminado(): string
    {
        return 'id';
    }

    /** Datos que realmente se guardan (permite transformar o descartar campos). */
    protected function atributos(array $validados, ?Model $registro): array
    {
        return $validados;
    }

    protected function despuesDeGuardar(Request $request, Model $registro): void
    {
    }

    /** Devuelve el motivo por el que NO se puede eliminar, o null si es posible. */
    protected function motivoBloqueoEliminar(Model $registro, bool $definitivo): ?string
    {
        return null;
    }

    protected function alEliminarDefinitivamente(Model $registro): void
    {
    }

    protected function usaPapelera(): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($this->modelo()), true);
    }

    protected function consulta(): Builder
    {
        $modelo = $this->modelo();

        return $modelo::query()->with($this->relaciones())->withCount($this->conteos());
    }

    protected function noExiste(string $mensaje = 'El registro solicitado no existe.')
    {
        return $this->error($mensaje, 404);
    }

    private function aplicarFiltros(Builder $consulta, Request $request): void
    {
        $buscar = trim((string) $request->query('buscar', ''));

        if ($buscar !== '' && $this->buscables()) {
            $consulta->where(function ($donde) use ($buscar) {
                foreach ($this->buscables() as $columna) {
                    $donde->orWhere($columna, 'like', "%{$buscar}%");
                }
            });
        }

        foreach ($this->filtros() as $columna) {
            if ($request->filled($columna)) {
                $consulta->where($columna, $request->query($columna));
            }
        }

        $ordenar = (string) $request->query('ordenar', $this->ordenPredeterminado());
        $columna = ltrim($ordenar, '-');

        if (! in_array($columna, $this->ordenables(), true)) {
            $ordenar = $this->ordenPredeterminado();
            $columna = ltrim($ordenar, '-');
        }

        $consulta->orderBy($columna, str_starts_with($ordenar, '-') ? 'desc' : 'asc');
    }

    public function index(Request $request)
    {
        $this->permitir($request, 'ver-registros');

        $consulta = $this->consulta();
        $this->aplicarFiltros($consulta, $request);

        return $this->paginado($consulta->paginate($this->porPagina($request))->withQueryString());
    }

    public function show(Request $request, $id)
    {
        $this->permitir($request, 'ver-registros');

        $registro = $this->consulta()->find($id);

        return $registro ? $this->ok($registro) : $this->noExiste();
    }

    public function store(Request $request)
    {
        $this->permitir($request, 'crear-registros', 'No tienes autorización para crear registros.');

        $validados = $request->validate($this->reglas(null), $this->mensajes());

        $modelo = $this->modelo();
        $registro = $modelo::create($this->atributos($validados, null));
        $this->despuesDeGuardar($request, $registro);

        return $this->creado(
            $this->consulta()->find($registro->getKey()),
            'Registro de '.$this->etiqueta().' creado correctamente.'
        );
    }

    /**
     * PUT reemplaza el recurso (todos los campos obligatorios se exigen);
     * PATCH lo actualiza parcialmente (solo se validan los campos enviados).
     */
    public function update(Request $request, $id)
    {
        $this->permitir($request, 'editar-registros', 'No tienes autorización para editar registros.');

        $modelo = $this->modelo();
        $registro = $modelo::find($id);

        if (! $registro) {
            return $this->noExiste();
        }

        $reglas = $this->reglas($registro);

        if ($request->isMethod('PATCH')) {
            $reglas = array_map(fn ($regla) => array_merge(['sometimes'], (array) $regla), $reglas);
        }

        $validados = $request->validate($reglas, $this->mensajes());

        $registro->fill($this->atributos($validados, $registro))->save();
        $this->despuesDeGuardar($request, $registro);

        return $this->ok(
            $this->consulta()->find($registro->getKey()),
            'Registro de '.$this->etiqueta().' actualizado correctamente.'
        );
    }

    /** Borrado lógico (o definitivo en recursos sin papelera). */
    public function destroy(Request $request, $id)
    {
        $this->permitir($request, 'eliminar-registros', 'No tienes autorización para eliminar registros.');

        $modelo = $this->modelo();
        $registro = $modelo::find($id);

        if (! $registro) {
            return $this->noExiste();
        }

        if ($motivo = $this->motivoBloqueoEliminar($registro, ! $this->usaPapelera())) {
            return $this->error($motivo, 409);
        }

        $registro->delete();

        return $this->sinContenido();
    }

    public function papelera(Request $request)
    {
        $this->permitir($request, 'ver-registros');

        $consulta = $this->consulta()->onlyTrashed();
        $this->aplicarFiltros($consulta, $request);

        return $this->paginado($consulta->paginate($this->porPagina($request))->withQueryString());
    }

    public function restaurar(Request $request, $id)
    {
        $this->permitir($request, 'eliminar-registros', 'No tienes autorización para restaurar registros.');

        $modelo = $this->modelo();
        $registro = $modelo::onlyTrashed()->find($id);

        if (! $registro) {
            return $this->noExiste('El registro no existe o no está en la papelera.');
        }

        $registro->restore();

        return $this->ok(
            $this->consulta()->find($registro->getKey()),
            'Registro de '.$this->etiqueta().' restaurado correctamente.'
        );
    }

    public function eliminarDefinitivamente(Request $request, $id)
    {
        $this->permitir($request, 'eliminar-registros', 'No tienes autorización para eliminar registros de forma definitiva.');

        $modelo = $this->modelo();
        $registro = $modelo::onlyTrashed()->find($id);

        if (! $registro) {
            return $this->noExiste('El registro no existe o no está en la papelera.');
        }

        if ($motivo = $this->motivoBloqueoEliminar($registro, true)) {
            return $this->error($motivo, 409);
        }

        $this->alEliminarDefinitivamente($registro);
        $registro->forceDelete();

        return $this->sinContenido();
    }
}
