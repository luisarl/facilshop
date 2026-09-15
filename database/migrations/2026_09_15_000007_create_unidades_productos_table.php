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
        Schema::create('unidades_productos', function (Blueprint $table)
        {
            $table->id('id_unidad');
            $table->string('nombre', 50)->unique();
            $table->string('abreviatura', 10)->unique();
            $table->boolean('permite_decimales')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unidades_productos');
    }
};
