<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolSeeder::class,
            CategoriaSeeder::class,
            UsuarioSeeder::class,
            ProductoSeeder::class,
            CarritoSeeder::class,
            CarritoItemSeeder::class,
            ListaDeseoSeeder::class,
            OrdenSeeder::class,
            OrdenDetalleSeeder::class,
            LogSeeder::class,
        ]);
    }
}
