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
    Schema::create('productos', function (Blueprint $table) {
        $table->id();
        $table->foreignId('categoria_id')->constrained('categorias')->onUpdate('cascade')->onDelete('restrict');
        $table->foreignId('usuario_id')->constrained('usuarios')->onUpdate('cascade')->onDelete('cascade');
        $table->string('nombre', 150);
        $table->text('descripcion')->nullable();
        $table->string('talla', 20)->nullable();
        $table->string('condicion', 50)->default('usado');
        $table->decimal('precio', 8, 2)->default(0);
        $table->unsignedInteger('stock')->default(1);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
