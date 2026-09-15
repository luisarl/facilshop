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
        Schema::create('monedas', function (Blueprint $table)
        {
            $table->id('id_moneda');
            $table->string('codigo', 10)->unique();
            $table->string('nombre', 50);
            $table->string('simbolo', 10);
            $table->decimal('tasa_cambio', 16, 4)->default(1.0000);
            $table->boolean('es_principal')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('codigo', 'idx_monedas_codigo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monedas');
    }
};
