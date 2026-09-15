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
        Schema::create('auditorias', function (Blueprint $table)
        {
            $table->id('id_auditoria');
            $table->foreignId('id_usuario')->nullable()->constrained('usuarios', 'id_usuario')->nullOnDelete();
            $table->string('modulo', 50);
            $table->string('accion', 50);
            $table->string('tabla_afectada', 100);
            $table->unsignedBigInteger('id_registro_afectado')->nullable();
            $table->json('valores_anteriores')->nullable();
            $table->json('valores_nuevos')->nullable();
            $table->string('ip_direccion', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('url', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('id_usuario', 'idx_auditorias_usuario');
            $table->index(['modulo', 'accion'], 'idx_auditorias_modulo_accion');
            $table->index(['tabla_afectada', 'id_registro_afectado'], 'idx_auditorias_tabla_registro');
            $table->index('created_at', 'idx_auditorias_fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};
