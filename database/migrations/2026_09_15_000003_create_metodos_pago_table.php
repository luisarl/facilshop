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
        Schema::create('metodos_pago', function (Blueprint $table)
        {
            $table->id('id_metodo_pago');
            $table->string('nombre', 100);
            $table->string('codigo', 50)->unique();
            $table->foreignId('id_moneda')->constrained('monedas', 'id_moneda')->onDelete('restrict');
            $table->enum('tipo', ['EFECTIVO', 'DIGITAL', 'TRANSFERENCIA', 'TARJETA', 'CREDITO', 'FINANCIAMIENTO'])->default('EFECTIVO');
            $table->boolean('requiere_referencia')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('id_moneda', 'idx_metodos_pago_moneda');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metodos_pago');
    }
};
