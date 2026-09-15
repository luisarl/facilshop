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
        Schema::create('inventario_conteos', function (Blueprint $table)
        {
            $table->id('id_conteo');
            $table->string('codigo_conteo', 50)->unique();
            $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario')->onDelete('restrict');
            $table->string('descripcion', 255)->nullable();
            $table->enum('estado', ['BORRADOR', 'EN_PROCESO', 'APLICADO', 'CANCELADO'])->default('BORRADOR');
            $table->timestamp('fecha_inicio')->useCurrent();
            $table->timestamp('fecha_cierre')->nullable();
            $table->integer('total_items_contados')->default(0);
            $table->integer('total_diferencia_unidades')->default(0);
            $table->decimal('total_diferencia_costo', 14, 2)->default(0.00);
            $table->timestamps();

            $table->index('estado', 'idx_conteos_estado');
            $table->index('codigo_conteo', 'idx_conteos_codigo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario_conteos');
    }
};
