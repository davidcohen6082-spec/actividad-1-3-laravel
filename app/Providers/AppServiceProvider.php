<?php

namespace App\Providers;

use App\Models\Administrador;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::define('ver-registros', function (Administrador $admin) {
            return $admin->nivel >= 1;
        });

        Gate::define('crear-registros', function (Administrador $admin) {
            return $admin->nivel >= 2;
        });

        Gate::define('editar-registros', function (Administrador $admin) {
            return $admin->nivel >= 2;
        });

        Gate::define('eliminar-registros', function (Administrador $admin) {
            return $admin->nivel >= 3;
        });

        Gate::define('gestionar-administradores', function (Administrador $admin) {
            return $admin->nivel >= 3;
        });
    }
}
