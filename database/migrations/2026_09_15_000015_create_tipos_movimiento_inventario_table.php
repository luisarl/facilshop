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
        Schema::create('tipos_movimiento_inventario', function (Blueprint $table)
        {
            $table->id('id_tipo_movimiento');
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 100);
            $table->enum('naturaleza', ['ENTRADA', 'SALIDA', 'AJUSTE']);
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('codigo', 'idx_tipos_movimiento_codigo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_movimiento_inventario');
    }
};
