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
        Schema::create('pagos_venta', function (Blueprint $table)
        {
            $table->id('id_pago_venta');
            $table->foreignId('id_venta')->constrained('ventas', 'id_venta')->onDelete('cascade');
            $table->foreignId('id_metodo_pago')->constrained('metodos_pago', 'id_metodo_pago')->onDelete('restrict');
            $table->foreignId('id_moneda')->constrained('monedas', 'id_moneda')->onDelete('restrict');
            $table->decimal('monto', 14, 2);
            $table->decimal('tasa_cambio', 16, 4);
            $table->decimal('monto_base', 14, 2);
            $table->string('referencia', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('id_metodo_pago', 'idx_pagos_venta_metodo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos_venta');
    }
};
