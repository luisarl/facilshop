<?php

namespace Tests\Feature;

use App\Models\ClasificacionProductosModel;
use App\Models\MarcasProductosModel;
use App\Models\MovimientosInventarioModel;
use App\Models\ProductosModel;
use App\Models\TiposMovimientoInventarioModel;
use App\Models\UnidadesProductosModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventarioAjustesTest extends TestCase
{
    use RefreshDatabase;

    protected User $usuario;
    protected UnidadesProductosModel $unidadPrincipal;
    protected UnidadesProductosModel $unidadSecundaria;
    protected TiposMovimientoInventarioModel $tipoEntrada;
    protected TiposMovimientoInventarioModel $tipoSalida;
    protected ProductosModel $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = User::factory()->create();

        $this->unidadPrincipal = UnidadesProductosModel::create([
            'nombre' => 'Unidad',
            'abreviatura' => 'UND',
            'activo' => true,
        ]);

        $this->unidadSecundaria = UnidadesProductosModel::create([
            'nombre' => 'Caja de 12',
            'abreviatura' => 'CJ12',
            'activo' => true,
        ]);

        $marca = MarcasProductosModel::create(['nombre' => 'Marca Test', 'activo' => true]);
        $categoria = ClasificacionProductosModel::create(['nombre' => 'Categoría Test', 'activo' => true]);

        $this->producto = ProductosModel::create([
            'sku' => 'PROD-TEST-001',
            'codigo_barras' => '7591234567890',
            'nombre' => 'Producto de Prueba',
            'id_marca' => $marca->id_marca,
            'id_categoria' => $categoria->id_categoria,
            'id_unidad' => $this->unidadPrincipal->id_unidad,
            'id_unidad_secundaria' => $this->unidadSecundaria->id_unidad,
            'equivalencia_unidad' => 1.0000,
            'equivalencia_unidad_secundaria' => 12.0000,
            'precio_costo' => 10.0000,
            'precio_venta' => 15.0000,
            'stock_actual' => 50,
            'stock_minimo' => 10,
            'activo' => true,
        ]);

        $this->tipoEntrada = TiposMovimientoInventarioModel::create([
            'codigo' => 'ENTRADA_AJUSTE',
            'nombre' => 'Entrada por Ajuste',
            'naturaleza' => 'ENTRADA',
            'activo' => true,
        ]);

        $this->tipoSalida = TiposMovimientoInventarioModel::create([
            'codigo' => 'SALIDA_MERMA',
            'nombre' => 'Salida por Merma',
            'naturaleza' => 'SALIDA',
            'activo' => true,
        ]);
    }

    public function test_se_puede_registrar_ajuste_de_entrada_e_incrementa_stock(): void
    {
        $payload = [
            'naturaleza' => 'ENTRADA',
            'id_tipo_movimiento' => $this->tipoEntrada->id_tipo_movimiento,
            'motivo' => 'Recepción extraordinaria de mercancía',
            'documento_referencia' => 'REC-001',
            'detalles' => [
                [
                    'id_producto' => $this->producto->id_producto,
                    'id_unidad' => $this->unidadPrincipal->id_unidad,
                    'cantidad' => 20,
                    'costo_unitario' => 10.00,
                    'observaciones' => 'Entrada por lote adicional',
                ]
            ],
        ];

        $response = $this->actingAs($this->usuario)->post(route('inventario.ajustes.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('inventario_ajustes', [
            'id_tipo_movimiento' => $this->tipoEntrada->id_tipo_movimiento,
            'motivo' => 'Recepción extraordinaria de mercancía',
            'total_items' => 20,
        ]);

        $this->producto->refresh();
        $this->assertEquals(70, $this->producto->stock_actual);

        $this->assertDatabaseHas('movimientos_inventario', [
            'id_producto' => $this->producto->id_producto,
            'id_tipo_movimiento' => $this->tipoEntrada->id_tipo_movimiento,
            'cantidad' => 20,
            'stock_anterior' => 50,
            'nuevo_stock' => 70,
        ]);
    }

    public function test_se_puede_registrar_ajuste_de_salida_y_deduce_stock(): void
    {
        $payload = [
            'naturaleza' => 'SALIDA',
            'id_tipo_movimiento' => $this->tipoSalida->id_tipo_movimiento,
            'motivo' => 'Baja por producto vencido',
            'documento_referencia' => 'MER-002',
            'detalles' => [
                [
                    'id_producto' => $this->producto->id_producto,
                    'id_unidad' => $this->unidadPrincipal->id_unidad,
                    'cantidad' => 10,
                    'costo_unitario' => 10.00,
                ]
            ],
        ];

        $response = $this->actingAs($this->usuario)->post(route('inventario.ajustes.store'), $payload);

        $response->assertRedirect();
        $this->producto->refresh();
        $this->assertEquals(40, $this->producto->stock_actual);

        $this->assertDatabaseHas('movimientos_inventario', [
            'id_producto' => $this->producto->id_producto,
            'id_tipo_movimiento' => $this->tipoSalida->id_tipo_movimiento,
            'cantidad' => 10,
            'stock_anterior' => 50,
            'nuevo_stock' => 40,
        ]);
    }

    public function test_ajuste_con_unidad_secundaria_convierte_a_unidades_base(): void
    {
        // 2 cajas de 12 = 24 unidades base
        $payload = [
            'naturaleza' => 'ENTRADA',
            'id_tipo_movimiento' => $this->tipoEntrada->id_tipo_movimiento,
            'motivo' => 'Entrada por bultos de cajas',
            'detalles' => [
                [
                    'id_producto' => $this->producto->id_producto,
                    'id_unidad' => $this->unidadSecundaria->id_unidad,
                    'cantidad' => 2,
                    'costo_unitario' => 120.00,
                ]
            ],
        ];

        $response = $this->actingAs($this->usuario)->post(route('inventario.ajustes.store'), $payload);

        $response->assertRedirect();
        $this->producto->refresh();
        // 50 + 24 = 74
        $this->assertEquals(74, $this->producto->stock_actual);

        $this->assertDatabaseHas('inventario_ajuste_detalles', [
            'id_producto' => $this->producto->id_producto,
            'cantidad' => 2,
            'cantidad_base' => 24,
            'stock_anterior' => 50,
            'nuevo_stock' => 74,
        ]);
    }

    public function test_no_se_permite_salida_que_exceda_stock_disponible(): void
    {
        $payload = [
            'naturaleza' => 'SALIDA',
            'id_tipo_movimiento' => $this->tipoSalida->id_tipo_movimiento,
            'motivo' => 'Intento de merma excesiva',
            'detalles' => [
                [
                    'id_producto' => $this->producto->id_producto,
                    'id_unidad' => $this->unidadPrincipal->id_unidad,
                    'cantidad' => 999, // Stock disponible es 50
                    'costo_unitario' => 10.00,
                ]
            ],
        ];

        $this->expectException(\Exception::class);

        $service = app(\App\Services\InventarioAjustesService::class);
        $service->RegistrarAjuste($payload, $this->usuario->id_usuario);
    }

    public function test_no_se_permite_tipo_movimiento_incompatible_con_la_naturaleza(): void
    {
        // Intentar registrar una ENTRADA con un tipo de movimiento SALIDA_MERMA
        $payload = [
            'naturaleza' => 'ENTRADA',
            'id_tipo_movimiento' => $this->tipoSalida->id_tipo_movimiento,
            'motivo' => 'Incoherencia intencional',
            'detalles' => [
                [
                    'id_producto' => $this->producto->id_producto,
                    'id_unidad' => $this->unidadPrincipal->id_unidad,
                    'cantidad' => 5,
                    'costo_unitario' => 10.00,
                ]
            ],
        ];

        $this->expectException(\Exception::class);

        $service = app(\App\Services\InventarioAjustesService::class);
        $service->RegistrarAjuste($payload, $this->usuario->id_usuario);
    }
}
