<?php

namespace App\Providers;

use App\Models\Administrador;
use App\Models\CarritoItem;
use App\Models\ListaDeseo;
use App\Models\Orden;
use App\Policies\CarritoItemPolicy;
use App\Policies\ListaDeseoPolicy;
use App\Policies\OrdenPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Límite de intentos en login/registro: 20 por minuto, por IP y por endpoint.
        RateLimiter::for('auth-api', fn (Request $request) => Limit::perMinute(20)->by($request->ip().'|'.$request->path()));

        /*
         * GATES (reglas generales del área administrativa).
         * Se evalúan sobre el administrador autenticado y leen su nivel guardado en la
         * base de datos: nunca un valor enviado por el cliente.
         *   Nivel 1: solo consulta.
         *   Nivel 2: consulta, crea, edita y gestiona pedidos / reportes.
         *   Nivel 3: todo lo anterior + eliminar (lógico y definitivo) y restaurar.
         */
        Gate::define('ver-registros', fn (Administrador $admin) => $admin->nivel >= 1);
        Gate::define('crear-registros', fn (Administrador $admin) => $admin->nivel >= 2);
        Gate::define('editar-registros', fn (Administrador $admin) => $admin->nivel >= 2);
        Gate::define('eliminar-registros', fn (Administrador $admin) => $admin->nivel >= 3);
        Gate::define('gestionar-administradores', fn (Administrador $admin) => $admin->nivel >= 3);
        Gate::define('gestionar-pedidos', fn (Administrador $admin) => $admin->nivel >= 2);
        Gate::define('ver-reportes', fn (Administrador $admin) => $admin->nivel >= 2);

        /*
         * POLICIES (reglas sobre un registro concreto del área cliente).
         */
        Gate::policy(Orden::class, OrdenPolicy::class);
        Gate::policy(ListaDeseo::class, ListaDeseoPolicy::class);
        Gate::policy(CarritoItem::class, CarritoItemPolicy::class);
    }
}
