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
        Schema::create('clientes', function (Blueprint $table)
        {
            $table->id('id_cliente');
            $table->string('identificacion', 20)->unique();
            $table->string('nombre', 150);
            $table->string('telefono', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->decimal('limite_credito', 14, 2)->default(0.00);
            $table->decimal('saldo_pendiente', 14, 2)->default(0.00);
            $table->timestamps();

            $table->index('identificacion', 'idx_clientes_identificacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
