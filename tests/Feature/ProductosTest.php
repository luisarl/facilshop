<?php

namespace Tests\Feature;

use App\Models\ClasificacionProductosModel;
use App\Models\MarcasProductosModel;
use App\Models\MonedasModel;
use App\Models\ProductosModel;
use App\Models\UnidadesProductosModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductosTest extends TestCase
{
    use RefreshDatabase;

    protected User $usuario;
    protected UnidadesProductosModel $unidad;
    protected MarcasProductosModel $marca;
    protected ClasificacionProductosModel $categoria;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = User::factory()->create();

        MonedasModel::create([
            'codigo' => 'USD',
            'nombre' => 'Dólar',
            'simbolo' => '$',
            'tasa_cambio' => 1.0000,
            'es_principal' => true,
            'activo' => true,
        ]);

        MonedasModel::create([
            'codigo' => 'VES',
            'nombre' => 'Bolívar',
            'simbolo' => 'Bs.',
            'tasa_cambio' => 85.0000,
            'es_principal' => false,
            'activo' => true,
        ]);

        $this->unidad = UnidadesProductosModel::create([
            'nombre' => 'Unidad',
            'abreviatura' => 'UND',
            'activo' => true,
        ]);

        $this->marca = MarcasProductosModel::create([
            'nombre' => 'Samsung',
            'activo' => true,
        ]);

        $this->categoria = ClasificacionProductosModel::create([
            'nombre' => 'Electrónica',
            'activo' => true,
        ]);
    }

    public function test_se_puede_listar_productos_con_precios_duales_usd_ves(): void
    {
        $producto = ProductosModel::create([
            'sku' => 'PROD-DUAL-01',
            'codigo_barras' => '7890123456789',
            'nombre' => 'Televisor LED 43"',
            'id_marca' => $this->marca->id_marca,
            'id_categoria' => $this->categoria->id_categoria,
            'id_unidad' => $this->unidad->id_unidad,
            'equivalencia_unidad' => 1.0000,
            'precio_costo' => 150.0000,
            'precio_venta' => 200.0000,
            'stock_actual' => 15,
            'stock_minimo' => 3,
            'activo' => true,
        ]);

        $response = $this->actingAs($this->usuario)->get(route('inventario.productos.index'));

        $response->assertOk();
    }

    public function test_se_puede_crear_producto_con_unidades_y_equivalencias(): void
    {
        $unidadCaja = UnidadesProductosModel::create([
            'nombre' => 'Caja de 24',
            'abreviatura' => 'CJ24',
            'activo' => true,
        ]);

        $payload = [
            'sku' => 'BEB-REF-001',
            'codigo_barras' => '7590001112223',
            'nombre' => 'Refresco Lata 355ml',
            'modelo' => 'Lata Clásica',
            'id_marca' => $this->marca->id_marca,
            'id_categoria' => $this->categoria->id_categoria,
            'id_unidad' => $this->unidad->id_unidad,
            'id_unidad_secundaria' => $unidadCaja->id_unidad,
            'equivalencia_unidad' => 1.0000,
            'equivalencia_unidad_secundaria' => 24.0000,
            'precio_costo' => 0.60,
            'precio_venta' => 1.25,
            'stock_actual' => 100,
            'stock_minimo' => 24,
            'activo' => true,
        ];

        $response = $this->actingAs($this->usuario)->post(route('inventario.productos.store'), $payload);

        $response->assertRedirect(route('inventario.productos.index'));
        $this->assertDatabaseHas('productos', [
            'sku' => 'BEB-REF-001',
            'codigo_barras' => '7590001112223',
            'nombre' => 'Refresco Lata 355ml',
            'equivalencia_unidad_secundaria' => 24.0000,
        ]);
    }

    public function test_se_puede_buscar_producto_por_codigo_de_barras(): void
    {
        ProductosModel::create([
            'sku' => 'BAR-001',
            'codigo_barras' => '7701234567890',
            'nombre' => 'Lector de Prueba',
            'id_marca' => $this->marca->id_marca,
            'id_categoria' => $this->categoria->id_categoria,
            'id_unidad' => $this->unidad->id_unidad,
            'equivalencia_unidad' => 1.0000,
            'precio_costo' => 10.0000,
            'precio_venta' => 20.0000,
            'stock_actual' => 20,
            'stock_minimo' => 5,
            'activo' => true,
        ]);

        $response = $this->actingAs($this->usuario)->get('/api/v1/products/barcode?codigo=7701234567890');

        $response->assertOk();
        $response->assertJsonPath('sku', 'BAR-001');
    }

    public function test_se_puede_actualizar_producto_manteniendo_sku_y_codigo_barras_y_descripcion(): void
    {
        $producto = ProductosModel::create([
            'sku' => 'EDIT-SKU-001',
            'codigo_barras' => '1234567890123',
            'nombre' => 'Producto Original',
            'descripcion' => 'Descripción inicial',
            'id_marca' => $this->marca->id_marca,
            'id_categoria' => $this->categoria->id_categoria,
            'id_unidad' => $this->unidad->id_unidad,
            'equivalencia_unidad' => 1.0000,
            'precio_costo' => 50.0000,
            'precio_venta' => 80.0000,
            'stock_actual' => 10,
            'stock_minimo' => 2,
            'activo' => true,
        ]);

        $payload = [
            'sku' => 'EDIT-SKU-001',
            'codigo_barras' => '1234567890123',
            'nombre' => 'Producto Modificado',
            'descripcion' => 'Nueva descripción detallada del producto',
            'id_marca' => $this->marca->id_marca,
            'id_categoria' => $this->categoria->id_categoria,
            'id_unidad' => $this->unidad->id_unidad,
            'equivalencia_unidad' => 1.0000,
            'precio_costo' => 55.0000,
            'precio_venta' => 90.0000,
            'stock_actual' => 10,
            'stock_minimo' => 2,
            'activo' => true,
        ];

        $response = $this->actingAs($this->usuario)->put(
            route('inventario.productos.update', $producto->id_producto),
            $payload
        );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('inventario.productos.index'));

        $this->assertDatabaseHas('productos', [
            'id_producto' => $producto->id_producto,
            'sku' => 'EDIT-SKU-001',
            'codigo_barras' => '1234567890123',
            'nombre' => 'Producto Modificado',
            'descripcion' => 'Nueva descripción detallada del producto',
            'precio_venta' => 90.0000,
        ]);
    }

    public function test_se_puede_crear_y_actualizar_producto_con_imagenes(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $archivoFoto1 = \Illuminate\Http\UploadedFile::fake()->image('foto1.jpg', 600, 600);
        $archivoFoto2 = \Illuminate\Http\UploadedFile::fake()->image('foto2.png', 800, 800);

        $payload = [
            'sku' => 'PROD-IMG-001',
            'codigo_barras' => '9998887776665',
            'nombre' => 'Producto con Imágenes',
            'descripcion' => 'Tiene múltiples imágenes',
            'id_marca' => $this->marca->id_marca,
            'id_categoria' => $this->categoria->id_categoria,
            'id_unidad' => $this->unidad->id_unidad,
            'equivalencia_unidad' => 1.0000,
            'precio_costo' => 10.00,
            'precio_venta' => 25.00,
            'stock_actual' => 5,
            'stock_minimo' => 1,
            'activo' => true,
            'imagen_principal' => $archivoFoto1,
            'imagenes' => [$archivoFoto2],
        ];

        $response = $this->actingAs($this->usuario)->post(
            route('inventario.productos.store'),
            $payload
        );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('inventario.productos.index'));

        $producto = ProductosModel::where('sku', 'PROD-IMG-001')->first();
        $this->assertNotNull($producto);
        $this->assertNotNull($producto->imagen_principal);
        $this->assertDatabaseHas('productos_imagenes', [
            'id_producto' => $producto->id_producto,
            'es_principal' => true,
        ]);
        $this->assertEquals(2, $producto->Imagenes()->count());
    }
}
