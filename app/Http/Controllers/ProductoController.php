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

        return view('admin.productos.form', compact('categorias'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'min:3', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'categoria_id' => ['required', 'exists:categorias,id'],
            'condicion' => ['required', 'in:nuevo,usado'],
            'talla' => ['nullable', 'string', 'max:20'],
            'precio' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'stock' => ['required', 'integer', 'min:0', 'max:1000'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
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
        ]);

        // Aún no hay login implementado; usamos un administrador existente como publicador
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

        if ($request->hasFile('imagen')) {
            $extension = $request->file('imagen')->getClientOriginalExtension();
            $nombreArchivo = "Producto_{$producto->id}_1.{$extension}";
            $request->file('imagen')->move(public_path('images/productos'), $nombreArchivo);

            $producto->update(['imagen' => 'images/productos/' . $nombreArchivo]);
        }

        return redirect()->route('admin.productos.index')->with('success', 'Producto creado correctamente.');
    }
}