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
    Schema::create('lista_deseos', function (Blueprint $table) {
        $table->id();
        $table->foreignId('usuario_id')->constrained('usuarios')->onUpdate('cascade')->onDelete('cascade');
        $table->foreignId('producto_id')->constrained('productos')->onUpdate('cascade')->onDelete('cascade');
        $table->timestamp('fecha_agregado')->useCurrent();
        $table->timestamps();
        $table->unique(['usuario_id', 'producto_id']);
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lista_deseos');
    }
};
