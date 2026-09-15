<?php

namespace Tests\Feature;

use App\Models\MonedasModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $usuario;
    protected MonedasModel $monedaUsd;
    protected MonedasModel $monedaVes;

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
    }

    public function test_pantalla_dashboard_se_renderiza_con_kpis_y_analitica(): void
    {
        $response = $this->actingAs($this->usuario)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('kpis')
            ->has('productos_stock_bajo')
            ->has('ventas_por_metodo')
            ->has('top_productos')
            ->has('ultimas_ventas')
            ->has('ultimas_auditorias')
        );
    }

    public function test_endpoint_api_metricas_dashboard(): void
    {
        $response = $this->getJson('/api/v1/reports/dashboard');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'kpis' => [
                'ventas_hoy_usd',
                'ventas_hoy_ves',
                'conteo_ventas_hoy',
                'ventas_mes_usd',
                'ventas_mes_ves',
                'conteo_ventas_mes',
                'ticket_promedio_usd',
                'total_cartera_usd',
                'total_cartera_ves',
                'clientes_con_deuda',
                'conteo_stock_bajo',
                'tasa_ves',
            ],
            'productos_stock_bajo',
            'ventas_por_metodo',
            'top_productos',
            'ultimas_ventas',
            'ultimas_auditorias',
        ]);
    }
}
