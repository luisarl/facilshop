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
        Schema::create('venta_detalles', function (Blueprint $table)
        {
            $table->id('id_venta_detalle');
            $table->foreignId('id_venta')->constrained('ventas', 'id_venta')->onDelete('cascade');
            $table->foreignId('id_producto')->constrained('productos', 'id_producto')->onDelete('restrict');
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 14, 4);
            $table->decimal('descuento', 14, 2)->default(0.00);
            $table->decimal('subtotal', 14, 2);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venta_detalles');
    }
};
