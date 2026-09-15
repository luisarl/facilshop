<?php

namespace Tests\Feature;

use App\Models\CajaTurnosModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CajaTurnosTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_se_puede_abrir_turno_de_caja(): void
    {
        $response = $this->actingAs($this->user)->post('/caja/abrir', [
            'monto_inicial' => 50.00,
            'observaciones' => 'Fondo de apertura',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/caja');

        $this->assertDatabaseHas('caja_turnos', [
            'id_usuario' => $this->user->id_usuario,
            'monto_inicial' => 50.00,
            'estado' => 'ABIERTA',
        ]);
    }

    public function test_no_se_puede_abrir_dos_turnos_simultaneos(): void
    {
        // Primer turno
        CajaTurnosModel::create([
            'id_usuario' => $this->user->id_usuario,
            'monto_inicial' => 30.00,
            'estado' => 'ABIERTA',
            'fecha_apertura' => now(),
        ]);

        // Intento de segundo turno
        $response = $this->actingAs($this->user)->post('/caja/abrir', [
            'monto_inicial' => 20.00,
        ]);

        $response->assertSessionHasErrors('monto_inicial');
        $this->assertEquals(1, CajaTurnosModel::where('id_usuario', $this->user->id_usuario)->count());
    }

    public function test_se_puede_cerrar_turno_con_calculo_de_diferencia(): void
    {
        $turno = CajaTurnosModel::create([
            'id_usuario' => $this->user->id_usuario,
            'monto_inicial' => 100.00,
            'estado' => 'ABIERTA',
            'fecha_apertura' => now(),
        ]);

        // Cierre declarando $95.00 (faltante de $5.00)
        $response = $this->actingAs($this->user)->post("/caja/{$turno->id_caja_turno}/cerrar", [
            'monto_final_declarado' => 95.00,
            'observaciones' => 'Cierre con faltante de $5',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/caja');

        $turnoActualizado = $turno->fresh();
        $this->assertEquals('CERRADA', $turnoActualizado->estado);
        $this->assertEquals(100.00, (float) $turnoActualizado->monto_final_teorico);
        $this->assertEquals(95.00, (float) $turnoActualizado->monto_final_declarado);
        $this->assertEquals(-5.00, (float) $turnoActualizado->diferencia);
        $this->assertNotNull($turnoActualizado->fecha_cierre);
    }
}
