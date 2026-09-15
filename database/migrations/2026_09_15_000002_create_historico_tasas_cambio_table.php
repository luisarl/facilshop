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
        Schema::create('historico_tasas_cambio', function (Blueprint $table)
        {
            $table->id('id_historico_tasa');
            $table->foreignId('id_moneda')->constrained('monedas', 'id_moneda')->onDelete('restrict');
            $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario')->onDelete('restrict');
            $table->decimal('tasa_anterior', 16, 4);
            $table->decimal('tasa_nueva', 16, 4);
            $table->string('observaciones', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['id_moneda', 'created_at'], 'idx_historico_moneda_fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historico_tasas_cambio');
    }
};
