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
    Schema::create('carrito_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('carrito_id')->constrained('carritos')->onUpdate('cascade')->onDelete('cascade');
        $table->foreignId('producto_id')->constrained('productos')->onUpdate('cascade')->onDelete('cascade');
        $table->unsignedInteger('cantidad')->default(1);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carrito_items');
    }
};
