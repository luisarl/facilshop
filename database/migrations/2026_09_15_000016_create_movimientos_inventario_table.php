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
        Schema::create('movimientos_inventario', function (Blueprint $table)
        {
            $table->id('id_movimiento_inventario');
            $table->foreignId('id_producto')->constrained('productos', 'id_producto')->onDelete('restrict');
            $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario')->onDelete('restrict');
            $table->foreignId('id_tipo_movimiento')->constrained('tipos_movimiento_inventario', 'id_tipo_movimiento')->onDelete('restrict');
            $table->integer('cantidad');
            $table->integer('stock_anterior');
            $table->integer('nuevo_stock');
            $table->string('motivo', 255)->nullable();
            $table->string('documento_referencia', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['id_producto', 'created_at'], 'idx_movimientos_producto');
            $table->index('id_tipo_movimiento', 'idx_movimientos_tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};
