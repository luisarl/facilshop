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
        Schema::create('inventario_ajustes', function (Blueprint $table)
        {
            $table->id('id_ajuste');
            $table->string('codigo_ajuste', 50)->unique();
            $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario')->onDelete('restrict');
            $table->foreignId('id_tipo_movimiento')->constrained('tipos_movimiento_inventario', 'id_tipo_movimiento')->onDelete('restrict');
            $table->string('motivo', 255);
            $table->string('documento_referencia', 100)->nullable();
            $table->enum('estado', ['BORRADOR', 'APLICADO', 'ANULADO'])->default('APLICADO');
            $table->timestamp('fecha_ajuste')->useCurrent();
            $table->integer('total_items')->default(0);
            $table->decimal('total_costo', 14, 2)->default(0.00);
            $table->timestamps();

            $table->index('codigo_ajuste', 'idx_ajustes_codigo');
            $table->index('id_tipo_movimiento', 'idx_ajustes_tipo');
            $table->index('estado', 'idx_ajustes_estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario_ajustes');
    }
};
