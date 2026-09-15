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
        Schema::create('inventario_ajuste_detalles', function (Blueprint $table)
        {
            $table->id('id_ajuste_detalle');
            $table->foreignId('id_ajuste')->constrained('inventario_ajustes', 'id_ajuste')->onDelete('cascade');
            $table->foreignId('id_producto')->constrained('productos', 'id_producto')->onDelete('restrict');
            $table->foreignId('id_unidad')->constrained('unidades_productos', 'id_unidad')->onDelete('restrict');
            $table->decimal('cantidad', 12, 4);
            $table->integer('cantidad_base');
            $table->decimal('costo_unitario', 14, 4);
            $table->decimal('costo_total', 14, 2);
            $table->integer('stock_anterior');
            $table->integer('nuevo_stock');
            $table->string('observaciones', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('id_producto', 'idx_ajuste_detalles_producto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario_ajuste_detalles');
    }
};
