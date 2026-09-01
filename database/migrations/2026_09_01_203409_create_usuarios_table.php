<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('usuarios', function (Blueprint $table) {
        $table->id();
        $table->foreignId('rol_id')->constrained('roles')->onUpdate('cascade')->onDelete('restrict');
        $table->string('nombre', 100);
        $table->string('email', 150)->unique();
        $table->string('password_hash')->nullable();
        $table->string('proveedor_social', 50)->nullable();
        $table->string('social_id')->nullable();
        $table->timestamp('fecha_registro')->useCurrent();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
