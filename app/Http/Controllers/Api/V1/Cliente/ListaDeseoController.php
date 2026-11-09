<?php

namespace App\Http\Controllers\Api\V1\Cliente;

use App\Http\Controllers\Api\V1\Concerns\RespuestasApi;
use App\Http\Controllers\Controller;
use App\Models\ListaDeseo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ListaDeseoController extends Controller
{
    use RespuestasApi;

    public function index(Request $request)
    {
        $deseos = $request->user()->listaDeseos()
            ->with('producto:id,nombre,precio,stock')
            ->orderByDesc('fecha_agregado')
            ->paginate($this->porPagina($request));

        return $this->paginado($deseos->withQueryString());
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'producto_id' => ['required', 'integer', Rule::exists('productos', 'id')->whereNull('deleted_at')],
        ], [
            'producto_id.required' => 'El producto_id es obligatorio.',
            'producto_id.exists' => 'El producto no existe o ya no está disponible.',
        ]);

        $cliente = $request->user();

        if ($cliente->listaDeseos()->where('producto_id', $datos['producto_id'])->exists()) {
            return $this->error('Ese producto ya está en tu lista de deseos.', 409);
        }

        $deseo = $cliente->listaDeseos()->create([
            'producto_id' => $datos['producto_id'],
            'fecha_agregado' => now(),
        ]);

        return $this->creado($deseo->load('producto:id,nombre,precio,stock'), 'Producto agregado a tu lista de deseos.');
    }

    public function destroy(Request $request, $id)
    {
        $deseo = ListaDeseo::find($id);

        if (! $deseo) {
            return $this->error('El elemento de la lista de deseos no existe.', 404);
        }

        $this->permitirSobre($request, 'delete', $deseo, 'No tienes autorización para eliminar este elemento.');

        $deseo->delete();

        return $this->sinContenido();
    }
}
