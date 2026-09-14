<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        $rolesIds = Rol::pluck('id');

        for ($i = 1; $i <= 15; $i++) {
            Usuario::create([
                'rol_id' => $rolesIds->random(),
                'nombre' => fake('es_MX')->name(),
                'email' => fake('es_MX')->unique()->safeEmail(),
                'password_hash' => bcrypt('password'),
                'proveedor_social' => fake()->randomElement([null, null, 'google', 'facebook']),
                'social_id' => null,
                'fecha_registro' => fake()->dateTimeBetween('-6 months', 'now'),
            ]);
        }
    }
}