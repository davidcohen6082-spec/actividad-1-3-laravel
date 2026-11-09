<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Concerns\RespuestasApi;
use App\Http\Controllers\Controller;
use App\Models\Orden;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteController extends Controller
{
    use RespuestasApi;

    /** Reporte resumen para el panel administrativo (nivel 2 o superior). */
    public function resumen(Request $request)
    {
        $this->permitir($request, 'ver-reportes', 'No tienes autorización para consultar reportes.');

        $porEstado = Orden::select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return $this->ok([
            'usuarios' => Usuario::count(),
            'clientes' => Usuario::whereHas('rol', fn ($q) => $q->where('nombre', 'cliente'))->count(),
            'productos' => Producto::count(),
            'productos_sin_stock' => Producto::where('stock', 0)->count(),
            'pedidos_por_estado' => collect(Orden::ESTADOS)->mapWithKeys(fn ($e) => [$e => (int) ($porEstado[$e] ?? 0)]),
            'ventas_completadas' => round((float) Orden::where('estado', 'completada')->sum('total'), 2),
        ]);
    }
}
