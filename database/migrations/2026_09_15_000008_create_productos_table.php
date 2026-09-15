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
        Schema::create('productos', function (Blueprint $table)
        {
            $table->id('id_producto');
            $table->string('sku', 50)->unique();
            $table->string('codigo_barras', 100)->nullable()->unique();
            $table->string('nombre', 150);
            $table->string('modelo', 100)->nullable();
            $table->text('descripcion')->nullable();
            $table->foreignId('id_marca')->nullable()->constrained('marcas_productos', 'id_marca')->nullOnDelete();
            $table->foreignId('id_categoria')->nullable()->constrained('clasificacion_productos', 'id_categoria')->nullOnDelete();
            $table->foreignId('id_unidad')->constrained('unidades_productos', 'id_unidad')->onDelete('restrict');
            $table->foreignId('id_unidad_secundaria')->nullable()->constrained('unidades_productos', 'id_unidad')->onDelete('restrict');
            $table->decimal('equivalencia_unidad', 12, 4)->default(1.0000);
            $table->decimal('equivalencia_unidad_secundaria', 12, 4)->default(1.0000);
            $table->decimal('precio_costo', 14, 4);
            $table->decimal('precio_venta', 14, 4);
            $table->integer('stock_actual')->default(0);
            $table->integer('stock_minimo')->default(5);
            $table->string('imagen_principal', 255)->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('sku', 'idx_productos_sku');
            $table->index('codigo_barras', 'idx_productos_codigo_barras');
            $table->index('id_marca', 'idx_productos_marca');
            $table->index('id_categoria', 'idx_productos_categoria');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
