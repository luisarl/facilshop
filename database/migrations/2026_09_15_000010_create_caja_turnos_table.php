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
        Schema::create('caja_turnos', function (Blueprint $table)
        {
            $table->id('id_caja_turno');
            $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario')->onDelete('restrict');
            $table->decimal('monto_inicial', 14, 2)->default(0.00);
            $table->decimal('monto_final_teorico', 14, 2)->nullable();
            $table->decimal('monto_final_declarado', 14, 2)->nullable();
            $table->decimal('diferencia', 14, 2)->nullable();
            $table->enum('estado', ['ABIERTA', 'CERRADA'])->default('ABIERTA');
            $table->timestamp('fecha_apertura')->useCurrent();
            $table->timestamp('fecha_cierre')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['id_usuario', 'estado'], 'idx_caja_turnos_estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caja_turnos');
    }
};
