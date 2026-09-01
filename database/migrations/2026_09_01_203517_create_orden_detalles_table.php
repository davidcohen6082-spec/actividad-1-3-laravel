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
    Schema::create('orden_detalles', function (Blueprint $table) {
        $table->id();
        $table->foreignId('orden_id')->constrained('ordenes')->onUpdate('cascade')->onDelete('cascade');
        $table->foreignId('producto_id')->constrained('productos')->onUpdate('cascade')->onDelete('restrict');
        $table->unsignedInteger('cantidad')->default(1);
        $table->decimal('precio_unitario', 8, 2);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orden_detalles');
    }
};
