<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Actividad 4.3: permite activar / desactivar clientes desde el módulo administrativo
 * (PATCH /api/v1/admin/usuarios/{id}/estado). Los administradores ya tenían "activo".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('social_id');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('activo');
        });
    }
};
