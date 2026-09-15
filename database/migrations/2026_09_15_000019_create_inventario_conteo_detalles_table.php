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
        Schema::create('inventario_conteo_detalles', function (Blueprint $table)
        {
            $table->id('id_conteo_detalle');
            $table->foreignId('id_conteo')->constrained('inventario_conteos', 'id_conteo')->onDelete('cascade');
            $table->foreignId('id_producto')->constrained('productos', 'id_producto')->onDelete('restrict');
            $table->integer('stock_teorico');
            $table->integer('stock_fisico');
            $table->integer('diferencia');
            $table->decimal('costo_unitario', 14, 4);
            $table->decimal('valor_diferencia', 14, 2);
            $table->string('observaciones', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('id_producto', 'idx_conteo_detalles_producto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario_conteo_detalles');
    }
};
