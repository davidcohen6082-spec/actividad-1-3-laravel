<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        $categoriaIds = Categoria::pluck('id');
        $usuarioIds = Usuario::pluck('id');

        $prendas = ['Camisa', 'Pantalón', 'Vestido', 'Chamarra', 'Sudadera', 'Falda', 'Short', 'Blusa', 'Abrigo', 'Playera'];
        $tallas = ['XS', 'S', 'M', 'L', 'XL', '32', '34', '36', '6', '8'];

        for ($i = 1; $i <= 20; $i++) {
            Producto::create([
                'categoria_id' => $categoriaIds->random(),
                'usuario_id' => $usuarioIds->random(),
                'nombre' => fake()->randomElement($prendas) . ' talla ' . fake()->randomElement($tallas),
                'descripcion' => fake('es_MX')->sentence(10),
                'talla' => fake()->randomElement($tallas),
                'condicion' => fake()->randomElement(['nuevo', 'usado']),
                'precio' => fake()->randomFloat(2, 20, 300),
                'stock' => fake()->numberBetween(1, 5),
            ]);
        }
    }
}