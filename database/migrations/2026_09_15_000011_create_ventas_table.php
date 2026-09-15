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
        Schema::create('ventas', function (Blueprint $table)
        {
            $table->id('id_venta');
            $table->foreignId('id_caja_turno')->constrained('caja_turnos', 'id_caja_turno')->onDelete('restrict');
            $table->foreignId('id_cliente')->constrained('clientes', 'id_cliente')->onDelete('restrict');
            $table->foreignId('id_moneda')->constrained('monedas', 'id_moneda')->onDelete('restrict');
            $table->decimal('tasa_cambio', 16, 4)->default(1.0000);
            $table->string('numero_comprobante', 50)->unique();
            $table->enum('tipo_comprobante', ['TICKET', 'BOLETA', 'FACTURA'])->default('TICKET');
            $table->decimal('subtotal', 14, 2);
            $table->decimal('descuento_total', 14, 2)->default(0.00);
            $table->decimal('impuesto', 14, 2)->default(0.00);
            $table->decimal('total', 14, 2);
            $table->decimal('total_moneda_base', 14, 2);
            $table->enum('estado', ['COMPLETADA', 'ANULADA'])->default('COMPLETADA');
            $table->timestamps();

            $table->index('numero_comprobante', 'idx_ventas_comprobante');
            $table->index('created_at', 'idx_ventas_fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
