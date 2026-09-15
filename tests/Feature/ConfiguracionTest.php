<?php

namespace Tests\Feature;

use App\Models\ClasificacionProductosModel;
use App\Models\MarcasProductosModel;
use App\Models\MonedasModel;
use App\Models\User;
use App\Models\UsuariosModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConfiguracionTest extends TestCase
{
    use RefreshDatabase;

    protected User $usuario;
    protected MonedasModel $monedaUsd;

    protected function setUp(): void
    {
        parent::setUp();

        $this->monedaUsd = MonedasModel::create([
            'codigo' => 'USD',
            'nombre' => 'Dólar Estadounidense',
            'simbolo' => '$',
            'tasa_cambio' => 1.0000,
            'es_principal' => true,
            'activo' => true,
        ]);

        $this->usuario = User::factory()->create([
            'rol' => 'superadmin',
            'activo' => true,
        ]);
    }

    #[Test]
    public function TestPantallaConfiguracionCargaCorrectamente(): void
    {
        $this->actingAs($this->usuario);

        $respuesta = $this->get(route('configuracion.index'));

        $respuesta->assertStatus(200);
        $respuesta->assertInertia(function (Assert $page)
        {
            $page->component('Configuracion/Index')
                ->has('usuarios')
                ->has('categorias')
                ->has('marcas')
                ->has('monedas')
                ->has('kpis')
                ->where('tabActivo', 'usuarios');
        });
    }

    #[Test]
    public function TestPuedeRegistrarNuevoUsuario(): void
    {
        $this->actingAs($this->usuario);

        $datosNuevo = [
            'nombre' => 'Carlos Gerente',
            'email' => 'carlos@empresa.com',
            'rol' => 'admin',
            'password' => 'claveSegura123',
            'activo' => true,
        ];

        $respuesta = $this->post(route('configuracion.usuarios.store'), $datosNuevo);

        $respuesta->assertRedirect(route('configuracion.index', ['tab' => 'usuarios']));
        $respuesta->assertSessionHas('success');

        $this->assertDatabaseHas('usuarios', [
            'email' => 'carlos@empresa.com',
            'nombre' => 'Carlos Gerente',
            'rol' => 'admin',
            'activo' => true,
        ]);
    }

    #[Test]
    public function TestPuedeActualizarUsuarioExistente(): void
    {
        $this->actingAs($this->usuario);

        $otroUsuario = User::factory()->create([
            'nombre' => 'Juan Cajero',
            'email' => 'juan@empresa.com',
            'rol' => 'cajero',
            'activo' => true,
        ]);

        $datosActualizar = [
            'nombre' => 'Juan Supervisor',
            'email' => 'juan.supervisor@empresa.com',
            'rol' => 'admin',
        ];

        $respuesta = $this->put(route('configuracion.usuarios.update', $otroUsuario->id_usuario), $datosActualizar);

        $respuesta->assertRedirect(route('configuracion.index', ['tab' => 'usuarios']));
        $respuesta->assertSessionHas('success');

        $this->assertDatabaseHas('usuarios', [
            'id_usuario' => $otroUsuario->id_usuario,
            'nombre' => 'Juan Supervisor',
            'email' => 'juan.supervisor@empresa.com',
            'rol' => 'admin',
        ]);
    }

    #[Test]
    public function TestNoPuedeDesactivarseASiMismo(): void
    {
        $this->actingAs($this->usuario);

        $respuesta = $this->patch(route('configuracion.usuarios.estado', $this->usuario->id_usuario));

        $respuesta->assertSessionHasErrors('error');
        $this->assertDatabaseHas('usuarios', [
            'id_usuario' => $this->usuario->id_usuario,
            'activo' => true,
        ]);
    }

    #[Test]
    public function TestPuedeAlternarEstadoDeOtroUsuario(): void
    {
        $this->actingAs($this->usuario);

        $otroUsuario = User::factory()->create([
            'activo' => true,
        ]);

        $respuesta = $this->patch(route('configuracion.usuarios.estado', $otroUsuario->id_usuario));

        $respuesta->assertRedirect(route('configuracion.index', ['tab' => 'usuarios']));
        $this->assertDatabaseHas('usuarios', [
            'id_usuario' => $otroUsuario->id_usuario,
            'activo' => false,
        ]);

        // Alternar nuevamente a activo
        $this->patch(route('configuracion.usuarios.estado', $otroUsuario->id_usuario));
        $this->assertDatabaseHas('usuarios', [
            'id_usuario' => $otroUsuario->id_usuario,
            'activo' => true,
        ]);
    }

    #[Test]
    public function TestPuedeRegistrarCategoriaRaizYSubcategoria(): void
    {
        $this->actingAs($this->usuario);

        $datosPadre = [
            'nombre' => 'Ferretería General',
            'descripcion' => 'Artículos para construcción y mantenimiento',
            'activo' => true,
        ];

        $respuestaPadre = $this->post(route('configuracion.categorias.store'), $datosPadre);
        $respuestaPadre->assertRedirect(route('configuracion.index', ['tab' => 'categorias']));

        $categoriaPadre = ClasificacionProductosModel::where('nombre', 'Ferretería General')->first();
        $this->assertNotNull($categoriaPadre);

        $datosHijo = [
            'nombre' => 'Tornillería y Fijación',
            'descripcion' => 'Pernos, tuercas y tornillos',
            'id_categoria_padre' => $categoriaPadre->id_categoria,
            'activo' => true,
        ];

        $respuestaHijo = $this->post(route('configuracion.categorias.store'), $datosHijo);
        $respuestaHijo->assertRedirect(route('configuracion.index', ['tab' => 'categorias']));

        $this->assertDatabaseHas('clasificacion_productos', [
            'nombre' => 'Tornillería y Fijación',
            'id_categoria_padre' => $categoriaPadre->id_categoria,
        ]);
    }

    #[Test]
    public function TestPuedeActualizarCategoria(): void
    {
        $this->actingAs($this->usuario);

        $categoria = ClasificacionProductosModel::create([
            'nombre' => 'Herramientas',
            'descripcion' => 'Herramientas básicas',
            'activo' => true,
        ]);

        $datosModificar = [
            'nombre' => 'Herramientas Manuales y Eléctricas',
            'descripcion' => 'Catálogo completo de herramientas',
        ];

        $respuesta = $this->put(route('configuracion.categorias.update', $categoria->id_categoria), $datosModificar);

        $respuesta->assertRedirect(route('configuracion.index', ['tab' => 'categorias']));
        $this->assertDatabaseHas('clasificacion_productos', [
            'id_categoria' => $categoria->id_categoria,
            'nombre' => 'Herramientas Manuales y Eléctricas',
        ]);
    }

    #[Test]
    public function TestPuedeAlternarEstadoCategoria(): void
    {
        $this->actingAs($this->usuario);

        $categoria = ClasificacionProductosModel::create([
            'nombre' => 'Pinturas',
            'activo' => true,
        ]);

        $respuesta = $this->patch(route('configuracion.categorias.estado', $categoria->id_categoria));

        $respuesta->assertRedirect(route('configuracion.index', ['tab' => 'categorias']));
        $this->assertDatabaseHas('clasificacion_productos', [
            'id_categoria' => $categoria->id_categoria,
            'activo' => false,
        ]);
    }

    #[Test]
    public function TestPuedeRegistrarMarca(): void
    {
        $this->actingAs($this->usuario);

        $datosMarca = [
            'nombre' => 'Stanley Tools',
            'descripcion' => 'Herramientas industriales de precisión',
            'activo' => true,
        ];

        $respuesta = $this->post(route('configuracion.marcas.store'), $datosMarca);

        $respuesta->assertRedirect(route('configuracion.index', ['tab' => 'marcas']));
        $this->assertDatabaseHas('marcas_productos', [
            'nombre' => 'Stanley Tools',
            'activo' => true,
        ]);
    }

    #[Test]
    public function TestPuedeActualizarMarca(): void
    {
        $this->actingAs($this->usuario);

        $marca = MarcasProductosModel::create([
            'nombre' => 'Bosch',
            'descripcion' => 'Línea alemana',
            'activo' => true,
        ]);

        $datosModificar = [
            'nombre' => 'Bosch Professional',
            'descripcion' => 'Línea azul para trabajo pesado',
        ];

        $respuesta = $this->put(route('configuracion.marcas.update', $marca->id_marca), $datosModificar);

        $respuesta->assertRedirect(route('configuracion.index', ['tab' => 'marcas']));
        $this->assertDatabaseHas('marcas_productos', [
            'id_marca' => $marca->id_marca,
            'nombre' => 'Bosch Professional',
        ]);
    }

    #[Test]
    public function TestPuedeAlternarEstadoMarca(): void
    {
        $this->actingAs($this->usuario);

        $marca = MarcasProductosModel::create([
            'nombre' => 'DeWalt',
            'activo' => true,
        ]);

        $respuesta = $this->patch(route('configuracion.marcas.estado', $marca->id_marca));

        $respuesta->assertRedirect(route('configuracion.index', ['tab' => 'marcas']));
        $this->assertDatabaseHas('marcas_productos', [
            'id_marca' => $marca->id_marca,
            'activo' => false,
        ]);
    }

    #[Test]
    public function TestValidacionDuplicadosEnUsuariosCategoriasYMarcas(): void
    {
        $this->actingAs($this->usuario);

        // Validación de correo duplicado en usuarios
        $respuestaUsuario = $this->post(route('configuracion.usuarios.store'), [
            'nombre' => 'Clon',
            'email' => $this->usuario->email,
            'rol' => 'cajero',
            'password' => 'secreto123',
        ]);
        $respuestaUsuario->assertSessionHasErrors('email');

        // Validación de categoría duplicada
        ClasificacionProductosModel::create([
            'nombre' => 'Electricidad',
            'activo' => true,
        ]);

        $respuestaCategoria = $this->post(route('configuracion.categorias.store'), [
            'nombre' => 'Electricidad',
        ]);
        $respuestaCategoria->assertSessionHasErrors('nombre');

        // Validación de marca duplicada
        MarcasProductosModel::create([
            'nombre' => 'Makita',
            'activo' => true,
        ]);

        $respuestaMarca = $this->post(route('configuracion.marcas.store'), [
            'nombre' => 'Makita',
        ]);
        $respuestaMarca->assertSessionHasErrors('nombre');
    }
}
