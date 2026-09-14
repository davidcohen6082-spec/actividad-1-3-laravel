<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            'Camisas', 'Pantalones', 'Calzado', 'Accesorios', 'Vestidos',
            'Chamarras', 'Ropa Deportiva', 'Ropa Infantil', 'Bolsas', 'Ropa de Cama',
        ];

        foreach ($categorias as $nombre) {
            Categoria::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
