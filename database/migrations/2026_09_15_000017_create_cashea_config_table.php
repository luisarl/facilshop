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
        Schema::create('cashea_config', function (Blueprint $table)
        {
            $table->id('id_cashea_config');
            $table->enum('modo_operacion', ['MANUAL', 'API_SANDBOX', 'API_PRODUCCION'])->default('MANUAL');
            $table->string('api_key', 255)->nullable();
            $table->string('api_secret', 255)->nullable();
            $table->string('merchant_id', 100)->nullable();
            $table->decimal('porcentaje_inicial_defecto', 5, 2)->default(40.00);
            $table->integer('cuotas_defecto')->default(3);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashea_config');
    }
};
