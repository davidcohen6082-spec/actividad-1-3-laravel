<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ValidaIdentificador;
use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Support\ApiRespuesta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CategoriaController extends Controller
{
    use ValidaIdentificador;

    /** GET /api/v1/categorias */
    public function index(Request $request)
    {
        $consulta = Categoria::query()->withCount('productos')->orderBy('nombre');

        if ($request->filled('buscar') && is_string($request->query('buscar'))) {
            $consulta->where('nombre', 'like', '%'.$request->query('buscar').'%');
        }

        $categorias = $consulta->get()->map(fn (Categoria $c) => $this->formato($c))->all();

        return ApiRespuesta::exito('Categorías consultadas correctamente', $categorias);
    }

    /** GET /api/v1/categorias/{id} */
    public function show($id)
    {
        if ($respuesta = $this->idInvalido($id)) {
            return $respuesta;
        }

        $categoria = Categoria::withCount('productos')->find($id);

        if (! $categoria) {
            return ApiRespuesta::error('La categoría solicitada no existe', 404);
        }

        return ApiRespuesta::exito('Categoría consultada correctamente', $this->formato($categoria));
    }

    /** POST /api/v1/categorias */
    public function store(Request $request)
    {
        $validador = $this->validador($request, null, true);

        if ($validador->fails()) {
            return ApiRespuesta::error('Los datos enviados no son válidos', 422, $validador->errors());
        }

        $categoria = Categoria::create(['nombre' => trim($validador->validated()['nombre'])]);
        $categoria->productos_count = 0;

        return ApiRespuesta::exito('Categoría creada correctamente', $this->formato($categoria), 201);
    }

    /** PUT (reemplazo completo) y PATCH (parcial) /api/v1/categorias/{id} */
    public function update(Request $request, $id)
    {
        if ($respuesta = $this->idInvalido($id)) {
            return $respuesta;
        }

        $categoria = Categoria::find($id);

        if (! $categoria) {
            return ApiRespuesta::error('La categoría solicitada no existe', 404);
        }

        $validador = $this->validador($request, (int) $id, $request->isMethod('PUT'));

        if ($validador->fails()) {
            return ApiRespuesta::error('Los datos enviados no son válidos', 422, $validador->errors());
        }

        $datos = $validador->validated();

        if (array_key_exists('nombre', $datos)) {
            $categoria->nombre = trim($datos['nombre']);
        }

        $categoria->save();
        $categoria->loadCount('productos');

        return ApiRespuesta::exito('Categoría actualizada correctamente', $this->formato($categoria));
    }

    /** DELETE /api/v1/categorias/{id} */
    public function destroy($id)
    {
        if ($respuesta = $this->idInvalido($id)) {
            return $respuesta;
        }

        $categoria = Categoria::find($id);

        if (! $categoria) {
            return ApiRespuesta::error('La categoría solicitada no existe', 404);
        }

        // La llave foránea es RESTRICT: una categoría con productos no puede eliminarse.
        if (DB::table('productos')->where('categoria_id', $categoria->id)->exists()) {
            return ApiRespuesta::error(
                'No se puede eliminar la categoría porque tiene productos asociados',
                409,
                ['categoria' => ['Reasigna o elimina primero los productos de esta categoría.']]
            );
        }

        $categoria->delete();

        return ApiRespuesta::exito('Categoría eliminada correctamente', ['id' => (int) $id]);
    }

    private function validador(Request $request, ?int $ignorar, bool $completo)
    {
        return Validator::make($request->all(), [
            'nombre' => [
                $completo ? 'required' : 'sometimes', 'required', 'string', 'min:3', 'max:100',
                Rule::unique('categorias', 'nombre')->ignore($ignorar),
            ],
        ], [
            'nombre.required' => 'El campo nombre es obligatorio.',
            'nombre.string' => 'El nombre debe ser texto.',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'nombre.max' => 'El nombre no puede superar los 100 caracteres.',
            'nombre.unique' => 'Ya existe una categoría con ese nombre.',
        ]);
    }

    private function formato(Categoria $c): array
    {
        return [
            'id' => $c->id,
            'nombre' => $c->nombre,
            'productos_count' => (int) ($c->productos_count ?? 0),
            'created_at' => $c->created_at,
            'updated_at' => $c->updated_at,
        ];
    }
}
