<?php

namespace Tests\Feature;

use App\Models\CajaTurnosModel;
use App\Models\ClientesModel;
use App\Models\MetodosPagoModel;
use App\Models\MonedasModel;
use App\Models\MovimientosInventarioModel;
use App\Models\PagosVentaModel;
use App\Models\ProductosModel;
use App\Models\TiposMovimientoInventarioModel;
use App\Models\UnidadesProductosModel;
use App\Models\User;
use App\Models\VentaDetallesModel;
use App\Models\VentasModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacturacionTest extends TestCase
{
    use RefreshDatabase;

    protected User $usuario;
    protected MonedasModel $monedaUsd;
    protected MonedasModel $monedaVes;
    protected MetodosPagoModel $metodoEfectivoUsd;
    protected MetodosPagoModel $metodoCredito;
    protected ClientesModel $cliente;
    protected ProductosModel $producto;
    protected CajaTurnosModel $turno;
    protected TiposMovimientoInventarioModel $tipoSalidaVenta;
    protected TiposMovimientoInventarioModel $tipoDevolucion;

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

        $this->metodoCredito = MetodosPagoModel::create([
            'nombre' => 'Crédito Interno',
            'codigo' => 'CREDITO_TIENDA',
            'id_moneda' => $this->monedaUsd->id_moneda,
            'tipo' => 'CREDITO',
            'requiere_referencia' => false,
            'activo' => true,
        ]);

        $this->cliente = ClientesModel::create([
            'identificacion' => 'V-11223344',
            'nombre' => 'Carlos Delgado',
            'limite_credito' => 500.00,
            'saldo_pendiente' => 0.00,
        ]);

        $unidad = UnidadesProductosModel::create([
            'nombre' => 'Unidad',
            'abreviatura' => 'UND',
            'activo' => true,
        ]);

        $this->producto = ProductosModel::create([
            'sku' => 'PROD-001',
            'codigo_barras' => '7591234567890',
            'nombre' => 'Harina PAN 1kg',
            'id_unidad' => $unidad->id_unidad,
            'precio_costo' => 1.00,
            'precio_venta' => 1.50,
            'stock_actual' => 50,
            'stock_minimo' => 5,
            'activo' => true,
        ]);

        $this->tipoSalidaVenta = TiposMovimientoInventarioModel::create([
            'codigo' => 'SALIDA_VENTA',
            'nombre' => 'Salida por Venta',
            'naturaleza' => 'SALIDA',
            'activo' => true,
        ]);

        $this->tipoDevolucion = TiposMovimientoInventarioModel::create([
            'codigo' => 'DEVOLUCION',
            'nombre' => 'Devolución / Reverso',
            'naturaleza' => 'ENTRADA',
            'activo' => true,
        ]);

        $this->turno = CajaTurnosModel::create([
            'id_usuario' => $this->usuario->id_usuario,
            'monto_inicial' => 100.00,
            'estado' => 'ABIERTA',
        ]);
    }

    protected function CrearVentaPrueba(float $total = 15.00, bool $esCredito = false): VentasModel
    {
        $venta = VentasModel::create([
            'id_caja_turno' => $this->turno->id_caja_turno,
            'id_cliente' => $this->cliente->id_cliente,
            'id_moneda' => $this->monedaUsd->id_moneda,
            'tasa_cambio' => 85.0000,
            'numero_comprobante' => 'TKT-2026-' . rand(10000, 99999),
            'tipo_comprobante' => 'TICKET',
            'subtotal' => $total,
            'descuento_total' => 0.00,
            'impuesto' => 0.00,
            'total' => $total,
            'total_moneda_base' => $total,
            'estado' => 'COMPLETADA',
        ]);

        VentaDetallesModel::create([
            'id_venta' => $venta->id_venta,
            'id_producto' => $this->producto->id_producto,
            'cantidad' => 10,
            'precio_unitario' => 1.50,
            'descuento' => 0.00,
            'subtotal' => $total,
        ]);

        $metodoUsar = $esCredito ? $this->metodoCredito : $this->metodoEfectivoUsd;
        PagosVentaModel::create([
            'id_venta' => $venta->id_venta,
            'id_metodo_pago' => $metodoUsar->id_metodo_pago,
            'id_moneda' => $this->monedaUsd->id_moneda,
            'monto' => $total,
            'tasa_cambio' => 1.0000,
            'monto_base' => $total,
        ]);

        if ($esCredito)
        {
            $this->cliente->saldo_pendiente = (float) $this->cliente->saldo_pendiente + $total;
            $this->cliente->save();
        }

        // Deducir stock inicial de la venta
        $this->producto->stock_actual = (int) $this->producto->stock_actual - 10;
        $this->producto->save();

        return $venta;
    }

    public function test_se_puede_listar_facturacion_y_metricas(): void
    {
        $this->CrearVentaPrueba(30.00);

        $response = $this->actingAs($this->usuario)->get('/facturacion');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Facturacion/Index')
            ->has('ventas.data')
            ->has('metricas')
            ->where('metricas.total_hoy_usd', 30)
        );
    }

    public function test_se_puede_consultar_detalle_comprobante_con_datos_qr(): void
    {
        $venta = $this->CrearVentaPrueba(15.00);

        $response = $this->actingAs($this->usuario)->getJson("/facturacion/{$venta->id_venta}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'empresa',
            'venta',
            'tasa_ves',
            'total_ves',
            'qr_contenido',
            'qr_datos',
        ]);
        $this->assertStringContainsString($venta->numero_comprobante, $response->json('qr_contenido'));
    }

    public function test_se_puede_descargar_formato_escpos_raw(): void
    {
        $venta = $this->CrearVentaPrueba(15.00);

        $response = $this->actingAs($this->usuario)->get("/facturacion/{$venta->id_venta}/escpos");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
        $this->assertStringContainsString($venta->numero_comprobante, $response->getContent());
        $this->assertStringContainsString('FACIL SHOP', $response->getContent());
    }

    public function test_se_puede_anular_venta_y_reincorpora_inventario(): void
    {
        $venta = $this->CrearVentaPrueba(15.00); // 10 unidades descontadas, stock actual = 40
        $this->assertEquals(40, $this->producto->stock_actual);

        $response = $this->actingAs($this->usuario)
            ->post("/facturacion/{$venta->id_venta}/anular", [
                'motivo' => 'Cliente canceló la compra por motivos personales',
            ]);

        $response->assertRedirect();
        $venta->refresh();
        $this->assertEquals('ANULADA', $venta->estado);

        // Validar que el stock se incrementó de 40 a 50
        $this->producto->refresh();
        $this->assertEquals(50, $this->producto->stock_actual);

        // Validar que se creó el movimiento inmutable de devolución
        $this->assertDatabaseHas('movimientos_inventario', [
            'id_producto' => $this->producto->id_producto,
            'id_tipo_movimiento' => $this->tipoDevolucion->id_tipo_movimiento,
            'cantidad' => 10,
            'documento_referencia' => $venta->numero_comprobante,
        ]);
    }

    public function test_anular_venta_a_credito_reduce_saldo_deudor_del_cliente(): void
    {
        $venta = $this->CrearVentaPrueba(25.00, true);
        $this->cliente->refresh();
        $this->assertEquals(25.00, (float) $this->cliente->saldo_pendiente);

        $this->actingAs($this->usuario)
            ->post("/facturacion/{$venta->id_venta}/anular", [
                'motivo' => 'Error en factura de crédito',
            ]);

        $this->cliente->refresh();
        // Saldo deudor se redujo a 0
        $this->assertEquals(0.00, (float) $this->cliente->saldo_pendiente);
    }

    public function test_no_se_puede_anular_venta_ya_anulada(): void
    {
        $venta = $this->CrearVentaPrueba(15.00);
        $venta->estado = 'ANULADA';
        $venta->save();

        $response = $this->actingAs($this->usuario)
            ->post("/facturacion/{$venta->id_venta}/anular", [
                'motivo' => 'Intento repetido de anulación',
            ]);

        $response->assertSessionHasErrors(['error']);
    }
}
