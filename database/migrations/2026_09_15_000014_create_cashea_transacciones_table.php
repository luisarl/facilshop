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
        Schema::create('cashea_transacciones', function (Blueprint $table)
        {
            $table->id('id_cashea_transaccion');
            $table->foreignId('id_venta')->constrained('ventas', 'id_venta')->onDelete('cascade');
            $table->foreignId('id_cliente')->nullable()->constrained('clientes', 'id_cliente')->nullOnDelete();
            $table->string('cedula_cliente', 20);
            $table->string('telefono_cliente', 30)->nullable();
            $table->string('referencia_cashea', 100)->unique();
            $table->decimal('monto_total', 14, 2);
            $table->decimal('porcentaje_inicial', 5, 2)->default(40.00);
            $table->decimal('monto_inicial', 14, 2);
            $table->decimal('monto_financiado', 14, 2);
            $table->integer('numero_cuotas')->default(3);
            $table->decimal('monto_cuota', 14, 2);
            $table->enum('estado', ['PENDIENTE', 'APROBADA', 'RECHAZADA', 'CANCELADA'])->default('APROBADA');
            $table->enum('modo', ['MANUAL', 'API'])->default('MANUAL');
            $table->string('codigo_autorizacion', 50)->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index('referencia_cashea', 'idx_cashea_referencia');
            $table->index('cedula_cliente', 'idx_cashea_cedula');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashea_transacciones');
    }
};
