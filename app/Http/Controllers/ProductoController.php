<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function index()
    {
        $productos = Producto::with(['categoria', 'usuario'])->paginate(5);
        return view('admin.productos.index', compact('productos'));
    }

    public function create()
    {
        $categorias = Categoria::orderBy('nombre')->get();
        return view('admin.productos.form', ['producto' => null, 'categorias' => $categorias]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->reglas(), $this->mensajes());

        $usuarioId = Usuario::whereHas('rol', fn ($q) => $q->where('nombre', 'administrador'))->value('id')
            ?? Usuario::value('id');

        $producto = Producto::create([
            'categoria_id' => $validated['categoria_id'],
            'usuario_id' => $usuarioId,
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? null,
            'talla' => $validated['talla'] ?? null,
            'condicion' => $validated['condicion'],
            'precio' => $validated['precio'],
            'stock' => $validated['stock'],
        ]);

        $this->guardarImagen($request, $producto);
        $producto->save();

        return redirect()->route('admin.productos.index')->with('success', 'Producto creado correctamente.');
    }

    public function show($id)
    {
        $producto = Producto::with(['categoria', 'usuario'])->find($id);

        if (!$producto) {
            return redirect()->route('admin.productos.index')->with('error', 'El producto solicitado no existe.');
        }

        return view('admin.productos.show', compact('producto'));
    }

    public function edit($id)
    {
        $producto = Producto::find($id);

        if (!$producto) {
            return redirect()->route('admin.productos.index')->with('error', 'El producto solicitado no existe.');
        }

        $categorias = Categoria::orderBy('nombre')->get();
        return view('admin.productos.form', compact('producto', 'categorias'));
    }

    public function update(Request $request, $id)
    {
        $producto = Producto::find($id);

        if (!$producto) {
            return redirect()->route('admin.productos.index')->with('error', 'El producto solicitado no existe.');
        }

        $validated = $request->validate($this->reglas(), $this->mensajes());

        $producto->fill([
            'categoria_id' => $validated['categoria_id'],
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? null,
            'talla' => $validated['talla'] ?? null,
            'condicion' => $validated['condicion'],
            'precio' => $validated['precio'],
            'stock' => $validated['stock'],
        ]);

        $this->guardarImagen($request, $producto);
        $producto->save();

        return redirect()->route('admin.productos.index')->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy($id)
    {
        $producto = Producto::find($id);

        if (!$producto) {
            return redirect()->route('admin.productos.index')->with('error', 'El producto solicitado no existe.');
        }

        $producto->delete();

        return redirect()->route('admin.productos.index')->with('success', 'Producto eliminado. Puedes restaurarlo desde la papelera.');
    }

    public function papelera()
    {
        $productos = Producto::onlyTrashed()->with(['categoria', 'usuario'])->paginate(5);
        return view('admin.productos.papelera', compact('productos'));
    }

    public function restaurar($id)
    {
        $producto = Producto::onlyTrashed()->find($id);

        if (!$producto) {
            return redirect()->route('admin.productos.papelera')->with('error', 'El producto no existe o no está eliminado.');
        }

        $producto->restore();

        return redirect()->route('admin.productos.papelera')->with('success', 'Producto restaurado correctamente.');
    }

    public function forceDestroy($id)
    {
        $producto = Producto::onlyTrashed()->find($id);

        if (!$producto) {
            return redirect()->route('admin.productos.papelera')->with('error', 'El producto no existe o no está en la papelera.');
        }

        $enCarritos = $producto->carritoItems()->count();
        $enDeseos = $producto->listaDeseos()->count();
        $enOrdenes = $producto->ordenDetalles()->count();

        if ($enCarritos > 0 || $enDeseos > 0 || $enOrdenes > 0) {
            return redirect()->route('admin.productos.papelera')->with('error', 'No se puede eliminar definitivamente: el producto está referenciado en carritos, listas de deseos u órdenes.');
        }

        if ($producto->imagen && file_exists(public_path($producto->imagen))) {
            unlink(public_path($producto->imagen));
        }

        $producto->forceDelete();

        return redirect()->route('admin.productos.papelera')->with('success', 'Producto eliminado definitivamente.');
    }

    private function guardarImagen(Request $request, Producto $producto): void
    {
        if (!$request->hasFile('imagen')) {
            return;
        }

        if ($producto->imagen && file_exists(public_path($producto->imagen))) {
            unlink(public_path($producto->imagen));
        }

        $extension = $request->file('imagen')->getClientOriginalExtension();
        $nombreArchivo = "Producto_{$producto->id}_1.{$extension}";
        $request->file('imagen')->move(public_path('images/productos'), $nombreArchivo);

        $producto->imagen = 'images/productos/' . $nombreArchivo;
    }

    private function reglas(): array
    {
        return [
            'nombre' => ['required', 'string', 'min:3', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'categoria_id' => ['required', 'exists:categorias,id'],
            'condicion' => ['required', 'in:nuevo,usado'],
            'talla' => ['nullable', 'string', 'max:20'],
            'precio' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'stock' => ['required', 'integer', 'min:0', 'max:1000'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    private function mensajes(): array
    {
        return [
            'nombre.required' => 'El nombre del artículo es obligatorio.',
            'categoria_id.required' => 'Selecciona una categoría.',
            'categoria_id.exists' => 'La categoría seleccionada no es válida.',
            'condicion.required' => 'Selecciona la condición del artículo.',
            'precio.required' => 'El precio es obligatorio.',
            'precio.numeric' => 'El precio debe ser un número.',
            'precio.min' => 'El precio no puede ser negativo.',
            'stock.required' => 'El stock es obligatorio.',
            'stock.integer' => 'El stock debe ser un número entero.',
            'imagen.mimes' => 'La imagen debe ser jpg, jpeg, png o webp.',
            'imagen.max' => 'La imagen no debe superar los 2 MB.',
        ];
    }
}