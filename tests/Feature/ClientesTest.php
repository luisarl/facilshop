<?php

namespace Tests\Feature;

use App\Models\CajaTurnosModel;
use App\Models\ClientesModel;
use App\Models\MetodosPagoModel;
use App\Models\MonedasModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientesTest extends TestCase
{
    use RefreshDatabase;

    protected User $usuario;
    protected MonedasModel $monedaUsd;
    protected MonedasModel $monedaVes;
    protected MetodosPagoModel $metodoEfectivoUsd;
    protected ClientesModel $cliente;

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

        $this->cliente = ClientesModel::create([
            'identificacion' => 'V-11223344',
            'nombre' => 'Carlos Delgado',
            'telefono' => '04149876543',
            'email' => 'carlos@ejemplo.com',
            'limite_credito' => 500.00,
            'saldo_pendiente' => 150.00,
        ]);
    }

    public function test_se_puede_listar_clientes_y_metricas(): void
    {
        $response = $this->actingAs($this->usuario)->get('/clientes');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Clientes/Index')
            ->has('clientes.data')
            ->has('kpis')
            ->where('kpis.total_cartera_usd', 150)
        );
    }

    public function test_se_puede_crear_cliente(): void
    {
        $payload = [
            'identificacion' => 'V-99887766',
            'nombre' => 'María Valentina',
            'telefono' => '04121112233',
            'email' => 'maria@ejemplo.com',
            'limite_credito' => 300.00,
        ];

        $response = $this->actingAs($this->usuario)->post('/clientes', $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('clientes', [
            'identificacion' => 'V-99887766',
            'nombre' => 'María Valentina',
            'limite_credito' => 300.00,
            'saldo_pendiente' => 0.00,
        ]);
    }

    public function test_se_puede_actualizar_cliente(): void
    {
        $payload = [
            'identificacion' => 'V-11223344',
            'nombre' => 'Carlos Delgado Modificado',
            'telefono' => '04245556677',
            'email' => 'carlos.nuevo@ejemplo.com',
            'limite_credito' => 600.00,
        ];

        $response = $this->actingAs($this->usuario)
            ->put("/clientes/{$this->cliente->id_cliente}", $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('clientes', [
            'id_cliente' => $this->cliente->id_cliente,
            'nombre' => 'Carlos Delgado Modificado',
            'limite_credito' => 600.00,
        ]);
    }

    public function test_se_puede_abonar_a_deuda_de_cliente(): void
    {
        CajaTurnosModel::create([
            'id_usuario' => $this->usuario->id_usuario,
            'monto_inicial' => 50.00,
            'estado' => 'ABIERTA',
        ]);

        $payload = [
            'monto' => 50.00,
            'monto_base' => 50.00,
            'id_metodo_pago' => $this->metodoEfectivoUsd->id_metodo_pago,
            'id_moneda' => $this->monedaUsd->id_moneda,
            'tasa_cambio' => 1.0000,
            'referencia' => 'RECIBO-001',
            'observaciones' => 'Abono en efectivo',
        ];

        $response = $this->actingAs($this->usuario)
            ->post("/clientes/{$this->cliente->id_cliente}/abonar", $payload);

        $response->assertRedirect();
        $this->cliente->refresh();

        // 150 - 50 = 100
        $this->assertEquals(100.00, (float) $this->cliente->saldo_pendiente);
    }

    public function test_no_se_permite_abono_mayor_al_saldo_pendiente(): void
    {
        $payload = [
            'monto' => 200.00,
            'monto_base' => 200.00,
            'id_metodo_pago' => $this->metodoEfectivoUsd->id_metodo_pago,
            'id_moneda' => $this->monedaUsd->id_moneda,
            'tasa_cambio' => 1.0000,
        ];

        $response = $this->actingAs($this->usuario)
            ->post("/clientes/{$this->cliente->id_cliente}/abonar", $payload);

        $response->assertSessionHasErrors(['error']);
        $this->cliente->refresh();
        $this->assertEquals(150.00, (float) $this->cliente->saldo_pendiente);
    }

    public function test_se_puede_consultar_estado_de_cuenta(): void
    {
        $response = $this->actingAs($this->usuario)
            ->getJson("/clientes/{$this->cliente->id_cliente}/estado-cuenta");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'cliente',
            'ventas_credito',
            'saldo_pendiente_usd',
            'saldo_pendiente_ves',
            'limite_credito',
            'credito_disponible',
            'tasa_ves',
        ]);
        $this->assertEquals(350.00, $response->json('credito_disponible'));
    }
}
