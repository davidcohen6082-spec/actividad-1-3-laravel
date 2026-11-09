<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ValidaIdentificador;
use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Support\ApiRespuesta;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductoController extends Controller
{
    use ValidaIdentificador;

    private const DISCO = 'public';
    private const CARPETA = 'productos';

    /** GET /api/v1/productos  (filtros opcionales: buscar, categoria_id, condicion) */
    public function index(Request $request)
    {
        $validador = Validator::make($request->query(), [
            'buscar' => 'nullable|string|max:100',
            'categoria_id' => 'nullable|integer|min:1',
            'condicion' => 'nullable|in:nuevo,usado',
        ], [
            'categoria_id.integer' => 'El filtro categoria_id debe ser un número entero.',
            'condicion.in' => 'El filtro condicion solo admite los valores: nuevo o usado.',
            'buscar.max' => 'El filtro buscar no puede superar los 100 caracteres.',
        ]);

        if ($validador->fails()) {
            return ApiRespuesta::error('Los parámetros enviados no son válidos', 422, $validador->errors());
        }

        $consulta = Producto::query()->with('categoria')->orderByDesc('id');

        if ($request->filled('buscar')) {
            $consulta->where('nombre', 'like', '%'.$request->query('buscar').'%');
        }
        if ($request->filled('categoria_id')) {
            $consulta->where('categoria_id', $request->query('categoria_id'));
        }
        if ($request->filled('condicion')) {
            $consulta->where('condicion', $request->query('condicion'));
        }

        $productos = $consulta->get()->map(fn (Producto $p) => $this->formato($p))->all();

        return ApiRespuesta::exito('Productos consultados correctamente', $productos);
    }

    /** GET /api/v1/productos/{id} */
    public function show($id)
    {
        if ($respuesta = $this->idInvalido($id)) {
            return $respuesta;
        }

        $producto = Producto::with('categoria')->find($id);

        if (! $producto) {
            return ApiRespuesta::error('El producto solicitado no existe', 404);
        }

        return ApiRespuesta::exito('Producto consultado correctamente', $this->formato($producto));
    }

    /** POST /api/v1/productos  (JSON o multipart/form-data; la imagen es opcional) */
    public function store(Request $request)
    {
        $validador = $this->validador($request, true);

        if ($validador->fails()) {
            return ApiRespuesta::error('Los datos enviados no son válidos', 422, $validador->errors());
        }

        $datos = $validador->validated();
        $ruta = null;

        try {
            if ($request->hasFile('imagen')) {
                $ruta = $this->guardarArchivo($request->file('imagen'));
            }

            $producto = new Producto();
            $producto->forceFill([
                'categoria_id' => $datos['categoria_id'],
                'usuario_id' => $datos['usuario_id'],
                'nombre' => trim($datos['nombre']),
                'descripcion' => $datos['descripcion'] ?? null,
                'talla' => $datos['talla'] ?? null,
                'condicion' => $datos['condicion'],
                'precio' => $datos['precio'],
                'stock' => $datos['stock'],
                'imagen' => $ruta,
            ])->save();
        } catch (\Throwable $e) {
            // Si falló el registro no se deja el archivo huérfano.
            if ($ruta) {
                $this->descartarArchivo($ruta);
            }
            throw $e;
        }

        return ApiRespuesta::exito('Producto creado correctamente', $this->formato($producto->load('categoria')), 201);
    }

    /**
     * PUT   /api/v1/productos/{id}  -> reemplaza el recurso (todos los campos obligatorios)
     * PATCH /api/v1/productos/{id}  -> actualiza solo los campos enviados
     */
    public function update(Request $request, $id)
    {
        if ($respuesta = $this->idInvalido($id)) {
            return $respuesta;
        }

        $producto = Producto::find($id);

        if (! $producto) {
            return ApiRespuesta::error('El producto solicitado no existe', 404);
        }

        $esPut = $request->isMethod('PUT');
        $validador = $this->validador($request, false, $esPut);

        if ($validador->fails()) {
            return ApiRespuesta::error('Los datos enviados no son válidos', 422, $validador->errors());
        }

        $datos = $validador->validated();
        unset($datos['imagen']); // la imagen se gestiona en /productos/{id}/imagen

        if ($esPut) {
            // Reemplazo completo: lo opcional que no llega se vacía.
            $datos += ['descripcion' => null, 'talla' => null];
        }

        if (isset($datos['nombre'])) {
            $datos['nombre'] = trim($datos['nombre']);
        }

        $producto->forceFill($datos)->save();

        return ApiRespuesta::exito('Producto actualizado correctamente', $this->formato($producto->load('categoria')));
    }

    /** DELETE /api/v1/productos/{id} */
    public function destroy($id)
    {
        if ($respuesta = $this->idInvalido($id)) {
            return $respuesta;
        }

        $producto = Producto::find($id);

        if (! $producto) {
            return ApiRespuesta::error('El producto solicitado no existe', 404);
        }

        $ruta = $producto->imagen;
        $borradoLogico = in_array(SoftDeletes::class, class_uses_recursive($producto), true);

        DB::transaction(fn () => $producto->delete());

        // Con borrado lógico el registro puede restaurarse, así que conserva su imagen.
        if (! $borradoLogico) {
            $this->liberarArchivo($ruta, (int) $id);
        }

        return ApiRespuesta::exito('Producto eliminado correctamente', ['id' => (int) $id]);
    }

    /** POST /api/v1/productos/{id}/imagen  (multipart/form-data, campo "imagen") */
    public function guardarImagen(Request $request, $id)
    {
        if ($respuesta = $this->idInvalido($id)) {
            return $respuesta;
        }

        $producto = Producto::find($id);

        if (! $producto) {
            return ApiRespuesta::error('El producto solicitado no existe', 404);
        }

        $validador = Validator::make($request->all(), [
            'imagen' => 'required|'.$this->reglaImagen(),
        ], $this->mensajes(), $this->atributos());

        if ($validador->fails()) {
            return ApiRespuesta::error('Los datos enviados no son válidos', 422, $validador->errors());
        }

        $anterior = $producto->imagen;
        $nueva = $this->guardarArchivo($request->file('imagen'));

        try {
            $producto->forceFill(['imagen' => $nueva])->save();
        } catch (\Throwable $e) {
            $this->descartarArchivo($nueva);
            throw $e;
        }

        $this->liberarArchivo($anterior, (int) $id);

        return ApiRespuesta::exito(
            $anterior ? 'Imagen del producto actualizada correctamente' : 'Imagen del producto cargada correctamente',
            $this->formato($producto->load('categoria')),
            $anterior ? 200 : 201
        );
    }

    /** DELETE /api/v1/productos/{id}/imagen */
    public function eliminarImagen($id)
    {
        if ($respuesta = $this->idInvalido($id)) {
            return $respuesta;
        }

        $producto = Producto::find($id);

        if (! $producto) {
            return ApiRespuesta::error('El producto solicitado no existe', 404);
        }

        if (! $producto->imagen) {
            return ApiRespuesta::error('El producto no tiene una imagen asociada', 404);
        }

        $ruta = $producto->imagen;
        $producto->forceFill(['imagen' => null])->save();
        $this->liberarArchivo($ruta, (int) $id);

        return ApiRespuesta::exito('Imagen del producto eliminada correctamente', $this->formato($producto->load('categoria')));
    }

    // ------------------------------------------------------------------ utilidades

    private function validador(Request $request, bool $creando, bool $completo = true)
    {
        $obligatorio = $completo ? 'required' : 'sometimes|required';

        $existe = fn (string $tabla) => Rule::exists($tabla, 'id')->where(
            fn ($q) => Schema::hasColumn($tabla, 'deleted_at') ? $q->whereNull('deleted_at') : $q
        );

        return Validator::make($request->all(), [
            'nombre' => $obligatorio.'|string|min:3|max:150',
            'descripcion' => 'nullable|string|max:1000',
            'categoria_id' => [$completo ? 'required' : 'sometimes', 'required', 'integer', $existe('categorias')],
            // El dueño solo es obligatorio al crear; en actualizaciones se conserva si no se envía.
            'usuario_id' => [$creando ? 'required' : 'sometimes', 'required', 'integer', $existe('usuarios')],
            'talla' => 'nullable|string|max:20',
            'condicion' => $obligatorio.'|in:nuevo,usado',
            'precio' => $obligatorio.'|numeric|min:0.01|max:999999.99',
            'stock' => $obligatorio.'|integer|min:0|max:100000',
            'imagen' => 'nullable|'.$this->reglaImagen(),
        ], $this->mensajes(), $this->atributos());
    }

    private function atributos(): array
    {
        return ['categoria_id' => 'categoría', 'usuario_id' => 'usuario', 'condicion' => 'condición', 'descripcion' => 'descripción'];
    }

    private function reglaImagen(): string
    {
        return 'file|image|mimes:jpg,jpeg,png,webp|max:2048';
    }

    private function mensajes(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'numeric' => 'El campo :attribute debe ser un valor numérico.',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'nombre.max' => 'El nombre no puede superar los 150 caracteres.',
            'descripcion.max' => 'La descripción no puede superar los 1000 caracteres.',
            'talla.max' => 'La talla no puede superar los 20 caracteres.',
            'categoria_id.exists' => 'La categoría indicada no existe.',
            'usuario_id.exists' => 'El usuario indicado no existe.',
            'condicion.in' => 'La condición solo admite los valores: nuevo o usado.',
            'precio.min' => 'El precio debe ser mayor a 0.',
            'precio.max' => 'El precio no puede superar 999999.99.',
            'stock.min' => 'El stock no puede ser negativo.',
            'stock.max' => 'El stock no puede superar 100000 unidades.',
            'imagen.file' => 'El campo imagen debe ser un archivo.',
            'imagen.image' => 'El archivo debe ser una imagen.',
            'imagen.mimes' => 'La imagen debe ser de tipo jpg, jpeg, png o webp.',
            'imagen.max' => 'La imagen no puede superar los 2 MB.',
            'imagen.uploaded' => 'No se pudo cargar la imagen; verifica que no supere los 2 MB.',
        ];
    }

    /**
     * Guarda el archivo en el disco público (storage/app/public/productos) con un nombre
     * seguro generado por el servidor. Devuelve la ruta que se guarda en la BD:
     * "storage/productos/<archivo>", que funciona igual que las rutas de la parte web
     * (asset($producto->imagen) y public_path($producto->imagen)) gracias a `storage:link`.
     */
    private function guardarArchivo(UploadedFile $archivo): string
    {
        $extension = strtolower($archivo->guessExtension() ?: 'jpg');
        $nombre = 'Producto_'.now()->format('YmdHis').'_'.Str::lower(Str::random(10)).'.'.$extension;

        $archivo->storeAs(self::CARPETA, $nombre, self::DISCO);

        return 'storage/'.self::CARPETA.'/'.$nombre;
    }

    /** Borra el archivo solo si ningún otro producto (ni siquiera uno eliminado lógicamente) lo usa. */
    private function liberarArchivo(?string $ruta, int $exceptoId): void
    {
        if (! $ruta) {
            return;
        }

        $enUso = DB::table('productos')->where('imagen', $ruta)->where('id', '!=', $exceptoId)->exists();

        if ($enUso) {
            return;
        }

        if (str_starts_with($ruta, 'storage/'.self::CARPETA.'/')) {
            // Subida por la API (disco público).
            Storage::disk(self::DISCO)->delete(Str::after($ruta, 'storage/'));
        } elseif (str_starts_with($ruta, 'images/productos/') && ! str_contains($ruta, '..')) {
            // Subida anterior desde la parte web (public/images/productos).
            File::delete(public_path($ruta));
        }
    }

    /** Elimina un archivo recién guardado cuando falla el registro en la BD. */
    private function descartarArchivo(string $ruta): void
    {
        Storage::disk(self::DISCO)->delete(Str::after($ruta, 'storage/'));
    }

    private function formato(Producto $p): array
    {
        return [
            'id' => $p->id,
            'categoria_id' => (int) $p->categoria_id,
            'categoria' => $p->relationLoaded('categoria') && $p->categoria
                ? ['id' => $p->categoria->id, 'nombre' => $p->categoria->nombre] : null,
            'usuario_id' => (int) $p->usuario_id,
            'nombre' => $p->nombre,
            'descripcion' => $p->descripcion,
            'talla' => $p->talla,
            'condicion' => $p->condicion,
            'precio' => (float) $p->precio,
            'stock' => (int) $p->stock,
            // ruta guardada en la BD vs. URL pública para consultarla
            'imagen' => $p->imagen,
            'imagen_url' => $p->imagen ? url($p->imagen) : null,
            'created_at' => $p->created_at,
            'updated_at' => $p->updated_at,
        ];
    }
}
