<?php

namespace App\Http\Controllers;

use App\Models\Producto;

class ProductoController extends Controller
{
    public function index()
    {
        $productos = Producto::with(['categoria', 'usuario'])->paginate(5);

        return view('admin.productos.index', compact('productos'));
    }
}