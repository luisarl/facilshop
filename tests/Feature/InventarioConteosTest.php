<?php

namespace Tests\Feature;

use App\Models\ClasificacionProductosModel;
use App\Models\InventarioConteosModel;
use App\Models\MarcasProductosModel;
use App\Models\MovimientosInventarioModel;
use App\Models\ProductosModel;
use App\Models\TiposMovimientoInventarioModel;
use App\Models\UnidadesProductosModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventarioConteosTest extends TestCase
{
    use RefreshDatabase;

    protected User $usuario;
    protected ProductosModel $productoA;
    protected ProductosModel $productoB;
    protected TiposMovimientoInventarioModel $tipoAjusteConteo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = User::factory()->create();

        $unidad = UnidadesProductosModel::create([
            'nombre' => 'Unidad',
            'abreviatura' => 'UND',
            'activo' => true,
        ]);

        $marca = MarcasProductosModel::create(['nombre' => 'Marca General', 'activo' => true]);
        $categoria = ClasificacionProductosModel::create(['nombre' => 'Categoría General', 'activo' => true]);

        $this->productoA = ProductosModel::create([
            'sku' => 'CONTEO-A',
            'codigo_barras' => '111111111',
            'nombre' => 'Producto Conteo A',
            'id_marca' => $marca->id_marca,
            'id_categoria' => $categoria->id_categoria,
            'id_unidad' => $unidad->id_unidad,
            'equivalencia_unidad' => 1.0000,
            'precio_costo' => 5.0000,
            'precio_venta' => 10.0000,
            'stock_actual' => 100,
            'stock_minimo' => 10,
            'activo' => true,
        ]);

        $this->productoB = ProductosModel::create([
            'sku' => 'CONTEO-B',
            'codigo_barras' => '222222222',
            'nombre' => 'Producto Conteo B',
            'id_marca' => $marca->id_marca,
            'id_categoria' => $categoria->id_categoria,
            'id_unidad' => $unidad->id_unidad,
            'equivalencia_unidad' => 1.0000,
            'precio_costo' => 20.0000,
            'precio_venta' => 30.0000,
            'stock_actual' => 50,
            'stock_minimo' => 5,
            'activo' => true,
        ]);

        $this->tipoAjusteConteo = TiposMovimientoInventarioModel::create([
            'codigo' => 'AJUSTE_CONTEO',
            'nombre' => 'Conciliación por Conteo Físico',
            'naturaleza' => 'AJUSTE',
            'activo' => true,
        ]);
    }

    public function test_se_puede_iniciar_sesion_de_conteo_y_congela_stock_teorico(): void
    {
        $payload = [
            'descripcion' => 'Auditoría física de prueba',
            'precargar_stock' => false,
        ];

        $response = $this->actingAs($this->usuario)->post(route('inventario.conteos.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('inventario_conteos', [
            'descripcion' => 'Auditoría física de prueba',
            'estado' => 'EN_PROCESO',
        ]);

        $conteo = InventarioConteosModel::first();
        $this->assertCount(2, $conteo->Detalles);

        $detalleA = $conteo->Detalles->firstWhere('id_producto', $this->productoA->id_producto);
        $this->assertEquals(100, $detalleA->stock_teorico);
        $this->assertEquals(0, $detalleA->stock_fisico);
    }

    public function test_se_pueden_actualizar_cantidades_fisicas_contadas(): void
    {
        $conteoService = app(\App\Services\InventarioConteosService::class);
        $conteo = $conteoService->IniciarConteo(['descripcion' => 'Toma física test'], $this->usuario->id_usuario);

        $detalleA = $conteo->Detalles->firstWhere('id_producto', $this->productoA->id_producto);
        $detalleB = $conteo->Detalles->firstWhere('id_producto', $this->productoB->id_producto);

        // Se cuentan 105 de A (+5 de sobrante) y 45 de B (-5 de faltante)
        $items = [
            [
                'id_conteo_detalle' => $detalleA->id_conteo_detalle,
                'stock_fisico' => 105,
                'observaciones' => 'Encontradas 5 adicionales en repisa',
            ],
            [
                'id_conteo_detalle' => $detalleB->id_conteo_detalle,
                'stock_fisico' => 45,
                'observaciones' => 'Faltan 5 unidades',
            ],
        ];

        $response = $this->actingAs($this->usuario)->post(
            route('inventario.conteos.detalles', $conteo->id_conteo),
            ['items' => $items]
        );

        $response->assertRedirect();
        $conteo->refresh();

        $this->assertEquals(150, $conteo->total_items_contados);
        $this->assertEquals(0, $conteo->total_diferencia_unidades); // +5 - 5 = 0
        // Impacto costo: (+5 * 5.00) + (-5 * 20.00) = +25.00 - 100.00 = -75.00
        $this->assertEquals(-75.00, (float) $conteo->total_diferencia_costo);
    }

    public function test_se_puede_aplicar_conteo_y_concilia_stock_generando_movimiento(): void
    {
        $conteoService = app(\App\Services\InventarioConteosService::class);
        $conteo = $conteoService->IniciarConteo(['descripcion' => 'Toma física test'], $this->usuario->id_usuario);

        $detalleA = $conteo->Detalles->firstWhere('id_producto', $this->productoA->id_producto);
        $detalleB = $conteo->Detalles->firstWhere('id_producto', $this->productoB->id_producto);

        $conteoService->ActualizarItemsConteo($conteo->id_conteo, [
            [
                'id_conteo_detalle' => $detalleA->id_conteo_detalle,
                'stock_fisico' => 105,
            ],
            [
                'id_conteo_detalle' => $detalleB->id_conteo_detalle,
                'stock_fisico' => 45,
            ],
        ]);

        $response = $this->actingAs($this->usuario)->post(
            route('inventario.conteos.aplicar', $conteo->id_conteo)
        );

        $response->assertRedirect();
        $conteo->refresh();

        $this->assertEquals('APLICADO', $conteo->estado);
        $this->assertNotNull($conteo->fecha_cierre);

        // Verificar que los stocks en la tabla productos se actualizaron
        $this->productoA->refresh();
        $this->productoB->refresh();

        $this->assertEquals(105, $this->productoA->stock_actual);
        $this->assertEquals(45, $this->productoB->stock_actual);

        // Verificar generación de movimientos
        $this->assertDatabaseHas('movimientos_inventario', [
            'id_producto' => $this->productoA->id_producto,
            'id_tipo_movimiento' => $this->tipoAjusteConteo->id_tipo_movimiento,
            'stock_anterior' => 100,
            'nuevo_stock' => 105,
            'cantidad' => 5,
        ]);

        $this->assertDatabaseHas('movimientos_inventario', [
            'id_producto' => $this->productoB->id_producto,
            'id_tipo_movimiento' => $this->tipoAjusteConteo->id_tipo_movimiento,
            'stock_anterior' => 50,
            'nuevo_stock' => 45,
            'cantidad' => 5,
        ]);
    }

    public function test_se_puede_cancelar_sesion_de_conteo(): void
    {
        $conteoService = app(\App\Services\InventarioConteosService::class);
        $conteo = $conteoService->IniciarConteo(['descripcion' => 'Toma cancelada'], $this->usuario->id_usuario);

        $response = $this->actingAs($this->usuario)->post(
            route('inventario.conteos.cancelar', $conteo->id_conteo)
        );

        $response->assertRedirect();
        $conteo->refresh();

        $this->assertEquals('CANCELADO', $conteo->estado);
    }
}
