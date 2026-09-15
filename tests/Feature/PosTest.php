<?php

namespace Tests\Feature;

use App\Models\CajaTurnosModel;
use App\Models\CasheaConfigModel;
use App\Models\ClasificacionProductosModel;
use App\Models\ClientesModel;
use App\Models\MarcasProductosModel;
use App\Models\MetodosPagoModel;
use App\Models\MonedasModel;
use App\Models\MovimientosInventarioModel;
use App\Models\ProductosModel;
use App\Models\TiposMovimientoInventarioModel;
use App\Models\UnidadesProductosModel;
use App\Models\User;
use App\Models\VentasModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosTest extends TestCase
{
    use RefreshDatabase;

    protected User $usuario;
    protected MonedasModel $monedaUsd;
    protected MonedasModel $monedaVes;
    protected MetodosPagoModel $metodoEfectivoUsd;
    protected MetodosPagoModel $metodoPagoMovilVes;
    protected MetodosPagoModel $metodoCashea;
    protected ClientesModel $cliente;
    protected ProductosModel $producto;
    protected TiposMovimientoInventarioModel $tipoSalidaVenta;
    protected CajaTurnosModel $turnoAbierto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = User::factory()->create();

        $this->monedaUsd = MonedasModel::create([
            'codigo' => 'USD',
            'nombre' => 'Dólar',
            'simbolo' => '$',
            'tasa_cambio' => 1.0000,
            'es_principal' => true,
            'activo' => true,
        ]);

        $this->monedaVes = MonedasModel::create([
            'codigo' => 'VES',
            'nombre' => 'Bolívar Digital',
            'simbolo' => 'Bs.',
            'tasa_cambio' => 85.0000,
            'es_principal' => false,
            'activo' => true,
        ]);

        $this->metodoEfectivoUsd = MetodosPagoModel::create([
            'nombre' => 'Efectivo USD',
            'codigo' => 'EFECTIVO_USD',
            'id_moneda' => $this->monedaUsd->id_moneda,
            'tipo' => 'EFECTIVO',
            'requiere_referencia' => false,
            'activo' => true,
        ]);

        $this->metodoPagoMovilVes = MetodosPagoModel::create([
            'nombre' => 'Pago Móvil VES',
            'codigo' => 'PAGO_MOVIL_VES',
            'id_moneda' => $this->monedaVes->id_moneda,
            'tipo' => 'DIGITAL',
            'requiere_referencia' => true,
            'activo' => true,
        ]);

        $this->metodoCashea = MetodosPagoModel::create([
            'nombre' => 'Cashea BNPL',
            'codigo' => 'CASHEA_USD',
            'id_moneda' => $this->monedaUsd->id_moneda,
            'tipo' => 'FINANCIAMIENTO',
            'requiere_referencia' => true,
            'activo' => true,
        ]);

        $this->cliente = ClientesModel::create([
            'identificacion' => 'V-12345678',
            'nombre' => 'Juan Pérez',
            'telefono' => '04141234567',
            'limite_credito' => 200.00,
            'saldo_pendiente' => 0.00,
        ]);

        $unidad = UnidadesProductosModel::create([
            'nombre' => 'Unidad',
            'abreviatura' => 'UND',
            'activo' => true,
        ]);

        $marca = MarcasProductosModel::create(['nombre' => 'Marca Genérica', 'activo' => true]);
        $categoria = ClasificacionProductosModel::create(['nombre' => 'Categoría General', 'activo' => true]);

        $this->producto = ProductosModel::create([
            'sku' => 'POS-PROD-001',
            'codigo_barras' => '7590001234567',
            'nombre' => 'Audífonos Bluetooth',
            'id_marca' => $marca->id_marca,
            'id_categoria' => $categoria->id_categoria,
            'id_unidad' => $unidad->id_unidad,
            'equivalencia_unidad' => 1.0000,
            'precio_costo' => 12.0000,
            'precio_venta' => 25.0000,
            'stock_actual' => 50,
            'stock_minimo' => 5,
            'activo' => true,
        ]);

        $this->tipoSalidaVenta = TiposMovimientoInventarioModel::create([
            'codigo' => 'SALIDA_VENTA',
            'nombre' => 'Salida por Venta POS',
            'naturaleza' => 'SALIDA',
            'activo' => true,
        ]);

        CasheaConfigModel::create([
            'modo_operacion' => 'MANUAL',
            'porcentaje_inicial_defecto' => 40.00,
            'cuotas_defecto' => 3,
            'activo' => true,
        ]);

        $this->turnoAbierto = CajaTurnosModel::create([
            'id_usuario' => $this->usuario->id_usuario,
            'fecha_apertura' => now(),
            'monto_inicial' => 100.00,
            'estado' => 'ABIERTA',
        ]);
    }

    public function test_no_se_puede_acceder_al_pos_sin_turno_de_caja_abierto(): void
    {
        // Cerrar el turno abierto
        $this->turnoAbierto->update(['estado' => 'CERRADA', 'fecha_cierre' => now()]);

        $response = $this->actingAs($this->usuario)->get(route('pos.index'));

        $response->assertRedirect(route('caja.index'));
        $response->assertSessionHas('warning');
    }

    public function test_se_puede_acceder_al_pos_con_turno_de_caja_abierto(): void
    {
        $response = $this->actingAs($this->usuario)->get(route('pos.index'));

        $response->assertOk();
    }

    public function test_checkout_procesa_venta_en_efectivo_y_deduce_inventario(): void
    {
        $payload = [
            'id_cliente' => $this->cliente->id_cliente,
            'tipo_comprobante' => 'TICKET',
            'detalles' => [
                [
                    'id_producto' => $this->producto->id_producto,
                    'id_unidad' => $this->producto->id_unidad,
                    'cantidad' => 2,
                    'precio_unitario' => 25.00,
                    'descuento' => 0.00,
                ]
            ],
            'pagos' => [
                [
                    'id_metodo_pago' => $this->metodoEfectivoUsd->id_metodo_pago,
                    'id_moneda' => $this->monedaUsd->id_moneda,
                    'monto' => 50.00,
                    'tasa_cambio' => 1.0000,
                    'monto_base' => 50.00,
                    'referencia' => null,
                ]
            ],
        ];

        $response = $this->actingAs($this->usuario)->postJson(route('pos.checkout'), $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('venta.total', '50.00');

        // Verificar que se haya creado la venta
        $this->assertDatabaseHas('ventas', [
            'id_caja_turno' => $this->turnoAbierto->id_caja_turno,
            'id_cliente' => $this->cliente->id_cliente,
            'total' => 50.00,
            'estado' => 'COMPLETADA',
        ]);

        // Verificar deducción de existencias en productos
        $this->producto->refresh();
        $this->assertEquals(48, $this->producto->stock_actual);

        // Verificar generación de movimiento de salida
        $this->assertDatabaseHas('movimientos_inventario', [
            'id_producto' => $this->producto->id_producto,
            'id_tipo_movimiento' => $this->tipoSalidaVenta->id_tipo_movimiento,
            'cantidad' => 2,
            'stock_anterior' => 50,
            'nuevo_stock' => 48,
        ]);

        // Verificar registro de pago
        $this->assertDatabaseHas('pagos_venta', [
            'id_metodo_pago' => $this->metodoEfectivoUsd->id_metodo_pago,
            'monto' => 50.00,
            'monto_base' => 50.00,
        ]);
    }

    public function test_checkout_multipago_con_usd_y_ves(): void
    {
        // Venta de $50 pagada con $20 en efectivo USD + 2,550 Bs en Pago Móvil (a tasa 85 = $30)
        $payload = [
            'id_cliente' => $this->cliente->id_cliente,
            'tipo_comprobante' => 'FACTURA',
            'detalles' => [
                [
                    'id_producto' => $this->producto->id_producto,
                    'id_unidad' => $this->producto->id_unidad,
                    'cantidad' => 2,
                    'precio_unitario' => 25.00,
                ]
            ],
            'pagos' => [
                [
                    'id_metodo_pago' => $this->metodoEfectivoUsd->id_metodo_pago,
                    'id_moneda' => $this->monedaUsd->id_moneda,
                    'monto' => 20.00,
                    'tasa_cambio' => 1.0000,
                    'monto_base' => 20.00,
                    'referencia' => null,
                ],
                [
                    'id_metodo_pago' => $this->metodoPagoMovilVes->id_metodo_pago,
                    'id_moneda' => $this->monedaVes->id_moneda,
                    'monto' => 2550.00, // 2550 / 85 = 30.00
                    'tasa_cambio' => 85.0000,
                    'monto_base' => 30.00,
                    'referencia' => 'PM-994821',
                ],
            ],
        ];

        $response = $this->actingAs($this->usuario)->postJson(route('pos.checkout'), $payload);

        $response->assertStatus(201);
        $ventaId = $response->json('venta.id_venta');

        $this->assertDatabaseHas('ventas', [
            'id_venta' => $ventaId,
            'tipo_comprobante' => 'FACTURA',
            'total' => 50.00,
        ]);

        $this->assertDatabaseCount('pagos_venta', 2);
    }

    public function test_checkout_con_financiamiento_cashea_registra_transaccion_y_cuotas(): void
    {
        // Compra de $100 con Cashea (40% inicial = $40, financiado = $60, 3 cuotas de $20)
        $productoCaro = ProductosModel::create([
            'sku' => 'LAPTOP-001',
            'nombre' => 'Laptop Gamer',
            'id_unidad' => $this->producto->id_unidad,
            'equivalencia_unidad' => 1.0000,
            'precio_costo' => 80.0000,
            'precio_venta' => 100.0000,
            'stock_actual' => 10,
            'stock_minimo' => 2,
            'activo' => true,
        ]);

        $payload = [
            'id_cliente' => $this->cliente->id_cliente,
            'tipo_comprobante' => 'TICKET',
            'detalles' => [
                [
                    'id_producto' => $productoCaro->id_producto,
                    'id_unidad' => $productoCaro->id_unidad,
                    'cantidad' => 1,
                    'precio_unitario' => 100.00,
                ]
            ],
            'pagos' => [
                [
                    'id_metodo_pago' => $this->metodoCashea->id_metodo_pago,
                    'id_moneda' => $this->monedaUsd->id_moneda,
                    'monto' => 100.00,
                    'tasa_cambio' => 1.0000,
                    'monto_base' => 100.00,
                    'referencia' => 'CSH-112233',
                ]
            ],
            'cashea' => [
                'cedula_cliente' => 'V-12345678',
                'telefono_cliente' => '04141234567',
                'referencia_cashea' => 'CSH-112233',
                'codigo_autorizacion' => 'AUTH-7788',
                'monto_total' => 100.00,
                'porcentaje_inicial' => 40.00,
                'monto_inicial' => 40.00,
                'monto_financiado' => 60.00,
                'numero_cuotas' => 3,
                'monto_cuota' => 20.00,
            ],
        ];

        $response = $this->actingAs($this->usuario)->postJson(route('pos.checkout'), $payload);

        $response->assertStatus(201);
        $ventaId = $response->json('venta.id_venta');

        $this->assertDatabaseHas('cashea_transacciones', [
            'id_venta' => $ventaId,
            'cedula_cliente' => 'V-12345678',
            'referencia_cashea' => 'CSH-112233',
            'monto_total' => 100.00,
            'monto_inicial' => 40.00,
            'monto_financiado' => 60.00,
            'monto_cuota' => 20.00,
            'numero_cuotas' => 3,
            'estado' => 'APROBADA',
        ]);
    }

    public function test_checkout_falla_si_stock_es_insuficiente(): void
    {
        $payload = [
            'id_cliente' => $this->cliente->id_cliente,
            'tipo_comprobante' => 'TICKET',
            'detalles' => [
                [
                    'id_producto' => $this->producto->id_producto,
                    'id_unidad' => $this->producto->id_unidad,
                    'cantidad' => 999, // Disponible: 50
                    'precio_unitario' => 25.00,
                ]
            ],
            'pagos' => [
                [
                    'id_metodo_pago' => $this->metodoEfectivoUsd->id_metodo_pago,
                    'id_moneda' => $this->monedaUsd->id_moneda,
                    'monto' => 24975.00,
                    'tasa_cambio' => 1.0000,
                    'monto_base' => 24975.00,
                ]
            ],
        ];

        $response = $this->actingAs($this->usuario)->postJson(route('pos.checkout'), $payload);

        $response->assertStatus(422);
        $response->assertJsonStructure(['error']);
    }

    public function test_simulador_cashea_calcula_plan_correctamente(): void
    {
        $response = $this->actingAs($this->usuario)->postJson(route('pos.cashea.simular'), [
            'monto' => 150.00,
            'porcentaje' => 40.00,
        ]);

        $response->assertOk();
        $response->assertJson([
            'monto_total' => 150.00,
            'porcentaje_inicial' => 40.00,
            'monto_inicial' => 60.00,
            'monto_financiado' => 90.00,
            'numero_cuotas' => 3,
            'monto_cuota' => 30.00,
        ]);
        $response->assertJsonCount(3, 'cuotas');
    }
}
