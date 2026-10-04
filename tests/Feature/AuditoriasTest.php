<?php

namespace Tests\Feature;

use App\Models\AuditoriasModel;
use App\Models\ClientesModel;
use App\Models\MonedasModel;
use App\Models\ProductosModel;
use App\Models\UnidadesProductosModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditoriasTest extends TestCase
{
    use RefreshDatabase;

    protected User $usuario;
    protected MonedasModel $monedaUsd;

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
    }

    public function test_creacion_de_modelo_genera_pista_de_auditoria_automaticamente(): void
    {
        $this->actingAs($this->usuario);

        $cliente = ClientesModel::create([
            'identificacion' => 'V-99881122',
            'nombre' => 'Pedro Castillo',
            'limite_credito' => 200.00,
            'saldo_pendiente' => 0.00,
        ]);

        $this->assertDatabaseHas('auditorias', [
            'modulo' => 'VENTAS',
            'accion' => 'CREAR',
            'tabla_afectada' => 'clientes',
            'id_registro_afectado' => $cliente->id_cliente,
            'id_usuario' => $this->usuario->id_usuario,
        ]);
    }

    public function test_actualizacion_de_modelo_captura_valores_anteriores_y_nuevos(): void
    {
        $this->actingAs($this->usuario);

        $unidad = UnidadesProductosModel::create([
            'nombre' => 'Unidad',
            'abreviatura' => 'UND',
            'activo' => true,
        ]);

        $producto = ProductosModel::create([
            'sku' => 'AUDIT-PROD-01',
            'codigo_barras' => '1234567890123',
            'nombre' => 'Arroz 1kg',
            'id_unidad' => $unidad->id_unidad,
            'precio_costo' => 0.80,
            'precio_venta' => 1.20,
            'stock_actual' => 100,
            'stock_minimo' => 10,
            'activo' => true,
        ]);

        // Actualizar precio de venta
        $producto->precio_venta = 1.50;
        $producto->save();

        $auditoriaActualizacion = AuditoriasModel::where('tabla_afectada', 'productos')
            ->where('accion', 'ACTUALIZAR')
            ->where('id_registro_afectado', $producto->id_producto)
            ->first();

        $this->assertNotNull($auditoriaActualizacion);
        $this->assertEquals('1.2000', $auditoriaActualizacion->valores_anteriores['precio_venta']);
        $this->assertEquals('1.50', $auditoriaActualizacion->valores_nuevos['precio_venta']);
    }

    public function test_se_puede_listar_auditorias_y_metricas(): void
    {
        $this->actingAs($this->usuario);

        ClientesModel::create([
            'identificacion' => 'V-12344321',
            'nombre' => 'Cliente Demo Test',
            'limite_credito' => 100.00,
            'saldo_pendiente' => 0.00,
        ]);

        $response = $this->get('/auditorias');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Auditorias/Index')
            ->has('auditorias.data')
            ->has('kpis')
            ->has('modulos')
            ->has('acciones')
        );
    }

    public function test_se_puede_consultar_diff_detallado(): void
    {
        $this->actingAs($this->usuario);

        $cliente = ClientesModel::create([
            'identificacion' => 'V-77665544',
            'nombre' => 'Cliente Diff Test',
            'limite_credito' => 150.00,
            'saldo_pendiente' => 0.00,
        ]);

        $cliente->nombre = 'Cliente Diff Modificado';
        $cliente->save();

        $auditoria = AuditoriasModel::where('tabla_afectada', 'clientes')
            ->where('accion', 'ACTUALIZAR')
            ->where('id_registro_afectado', $cliente->id_cliente)
            ->first();

        $response = $this->getJson("/auditorias/{$auditoria->id_auditoria}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'auditoria',
            'diff' => [
                '*' => ['campo', 'valor_anterior', 'valor_nuevo', 'tipo_cambio'],
            ],
        ]);
    }

    public function test_se_puede_exportar_auditorias_a_csv(): void
    {
        $this->actingAs($this->usuario);

        $response = $this->get('/auditorias/exportar/csv');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
        $this->assertStringContainsString('ID,FECHA_HORA,USUARIO,MODULO,ACCION', $response->getContent());
    }

    public function test_login_y_logout_registran_pistas_de_auditoria(): void
    {
        $usuario = User::factory()->create();

        // Login
        $this->post('/login', [
            'email' => $usuario->email,
            'password' => 'password',
        ]);

        $this->assertDatabaseHas('auditorias', [
            'modulo' => 'AUTH',
            'accion' => 'LOGIN',
            'id_registro_afectado' => $usuario->id_usuario,
        ]);

        // Logout
        $this->actingAs($usuario)->post('/logout');

        $this->assertDatabaseHas('auditorias', [
            'modulo' => 'AUTH',
            'accion' => 'LOGOUT',
            'id_registro_afectado' => $usuario->id_usuario,
        ]);
    }

    public function test_login_fallido_registra_pista_de_auditoria(): void
    {
        $this->post('/login', [
            'email' => 'no_existe@test.com',
            'password' => 'clave_invalida',
        ]);

        $this->assertDatabaseHas('auditorias', [
            'modulo' => 'AUTH',
            'accion' => 'LOGIN_FALLIDO',
        ]);
    }

    public function test_se_puede_consultar_historial_por_entidad(): void
    {
        $this->actingAs($this->usuario);

        $cliente = ClientesModel::create([
            'identificacion' => 'V-88889999',
            'nombre' => 'Cliente Historial Test',
            'limite_credito' => 500.00,
            'saldo_pendiente' => 0.00,
        ]);

        $cliente->nombre = 'Cliente Historial Modificado';
        $cliente->save();

        $response = $this->getJson("/auditorias/entidad/clientes/{$cliente->id_cliente}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'tabla',
            'id_registro',
            'total_eventos',
            'timeline' => [
                '*' => ['id_auditoria', 'accion', 'modulo', 'usuario', 'fecha_hora', 'resumen', 'diff'],
            ],
        ]);
        $this->assertEquals(2, $response->json('total_eventos'));
    }

    public function test_api_rest_auditorias_por_entidad_y_exportar(): void
    {
        $this->actingAs($this->usuario);

        $cliente = ClientesModel::create([
            'identificacion' => 'V-11112222',
            'nombre' => 'Cliente API Test',
            'limite_credito' => 100.00,
            'saldo_pendiente' => 0.00,
        ]);

        $responseEntidad = $this->getJson("/api/v1/audits/entity/clientes/{$cliente->id_cliente}");
        $responseEntidad->assertStatus(200);
        $responseEntidad->assertJsonPath('tabla', 'clientes');

        $responseExport = $this->get('/api/v1/audits/export');
        $responseExport->assertStatus(200);
        $this->assertStringContainsString('ID,FECHA_HORA', $responseExport->getContent());
    }
}
