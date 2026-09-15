<?php

namespace Tests\Feature;

use App\Models\HistoricoTasasCambioModel;
use App\Models\MonedasModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonedasTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected MonedasModel $monedaUsd;
    protected MonedasModel $monedaVes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->monedaUsd = MonedasModel::create([
            'codigo' => 'USD',
            'nombre' => 'Dólar Estadounidense',
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
    }

    public function test_se_puede_listar_monedas(): void
    {
        $response = $this->actingAs($this->user)->get('/monedas');

        $response->assertStatus(200);
    }

    public function test_se_puede_actualizar_tasa_de_moneda_secundaria_y_genera_historico(): void
    {
        $response = $this->actingAs($this->user)->post('/monedas/tasas', [
            'id_moneda' => $this->monedaVes->id_moneda,
            'tasa_cambio' => 88.5000,
            'observaciones' => 'Ajuste de prueba tasa BCV',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(88.5000, (float) $this->monedaVes->fresh()->tasa_cambio);

        // Verificar inserción automática en el histórico de tasas
        $this->assertDatabaseHas('historico_tasas_cambio', [
            'id_moneda' => $this->monedaVes->id_moneda,
            'id_usuario' => $this->user->id_usuario,
            'tasa_anterior' => 85.0000,
            'tasa_nueva' => 88.5000,
        ]);
    }

    public function test_no_se_puede_modificar_tasa_de_moneda_principal_usd(): void
    {
        $response = $this->actingAs($this->user)->post('/monedas/tasas', [
            'id_moneda' => $this->monedaUsd->id_moneda,
            'tasa_cambio' => 1.2500,
            'observaciones' => 'Intento inválido de cambiar moneda base',
        ]);

        $response->assertSessionHasErrors('tasa_cambio');
        $this->assertEquals(1.0000, (float) $this->monedaUsd->fresh()->tasa_cambio);
    }

    public function test_la_tasa_debe_ser_mayor_a_cero(): void
    {
        $response = $this->actingAs($this->user)->post('/monedas/tasas', [
            'id_moneda' => $this->monedaVes->id_moneda,
            'tasa_cambio' => 0,
        ]);

        $response->assertSessionHasErrors('tasa_cambio');
    }
}
