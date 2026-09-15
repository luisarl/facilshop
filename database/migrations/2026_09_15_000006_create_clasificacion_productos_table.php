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
        Schema::create('clasificacion_productos', function (Blueprint $table)
        {
            $table->id('id_categoria');
            $table->string('nombre', 100)->unique();
            $table->text('descripcion')->nullable();
            $table->foreignId('id_categoria_padre')->nullable()->constrained('clasificacion_productos', 'id_categoria')->nullOnDelete();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('nombre', 'idx_clasificacion_nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clasificacion_productos');
    }
};
