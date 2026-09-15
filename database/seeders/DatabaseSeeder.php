<?php

namespace Database\Seeders;

use App\Models\AuditoriasModel;
use App\Models\CajaTurnosModel;
use App\Models\CasheaConfigModel;
use App\Models\CasheaTransaccionesModel;
use App\Models\ClasificacionProductosModel;
use App\Models\ClientesModel;
use App\Models\HistoricoTasasCambioModel;
use App\Models\InventarioAjusteDetallesModel;
use App\Models\InventarioAjustesModel;
use App\Models\InventarioConteoDetallesModel;
use App\Models\InventarioConteosModel;
use App\Models\MarcasProductosModel;
use App\Models\MetodosPagoModel;
use App\Models\MonedasModel;
use App\Models\MovimientosInventarioModel;
use App\Models\ProductosImagenesModel;
use App\Models\ProductosModel;
use App\Models\TiposMovimientoInventarioModel;
use App\Models\UnidadesProductosModel;
use App\Models\UserModel;
use App\Models\UsuariosModel;
use App\Models\VentaDetallesModel;
use App\Models\VentasModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Usuarios Iniciales
        $superadmin = UsuariosModel::create([
            'nombre' => 'Super Administrador',
            'email' => 'admin@facilshop.com',
            'password' => Hash::make('admin1234'),
            'rol' => 'superadmin',
            'activo' => true,
        ]);

        $gerente = UsuariosModel::create([
            'nombre' => 'Administrador Tienda',
            'email' => 'gerente@facilshop.com',
            'password' => Hash::make('admin1234'),
            'rol' => 'admin',
            'activo' => true,
        ]);

        $cajero = UsuariosModel::create([
            'nombre' => 'Cajero Principal',
            'email' => 'cajero@facilshop.com',
            'password' => Hash::make('cajero1234'),
            'rol' => 'cajero',
            'activo' => true,
        ]);

        // 2. Monedas
        $MonedaUsd = MonedasModel::create([
            'codigo' => 'USD',
            'nombre' => 'Dólar Estadounidense',
            'simbolo' => '$',
            'tasa_cambio' => 1.0000,
            'es_principal' => true,
            'activo' => true,
        ]);

        $MonedaVes = MonedasModel::create([
            'codigo' => 'VES',
            'nombre' => 'Bolívar Digital',
            'simbolo' => 'Bs.',
            'tasa_cambio' => 85.0000,
            'es_principal' => false,
            'activo' => true,
        ]);

        $MonedaEur = MonedasModel::create([
            'codigo' => 'EUR',
            'nombre' => 'Euro',
            'simbolo' => '€',
            'tasa_cambio' => 0.9200,
            'es_principal' => false,
            'activo' => true,
        ]);

        // 3. Histórico Inicial de Tasas
        HistoricoTasasCambioModel::create([
            'id_moneda' => $MonedaUsd->id_moneda,
            'id_usuario' => $superadmin->id_usuario,
            'tasa_anterior' => 1.0000,
            'tasa_nueva' => 1.0000,
            'observaciones' => 'Moneda base del sistema (Fijo 1.0000)',
            'created_at' => now(),
        ]);

        HistoricoTasasCambioModel::create([
            'id_moneda' => $MonedaVes->id_moneda,
            'id_usuario' => $superadmin->id_usuario,
            'tasa_anterior' => 85.0000,
            'tasa_nueva' => 85.0000,
            'observaciones' => 'Tasa inicial BCV',
            'created_at' => now(),
        ]);

        HistoricoTasasCambioModel::create([
            'id_moneda' => $MonedaEur->id_moneda,
            'id_usuario' => $superadmin->id_usuario,
            'tasa_anterior' => 0.9200,
            'tasa_nueva' => 0.9200,
            'observaciones' => 'Tasa inicial Euro',
            'created_at' => now(),
        ]);

        // 4. Métodos de Pago
        MetodosPagoModel::create([
            'nombre' => 'Efectivo USD',
            'codigo' => 'EFECTIVO_USD',
            'id_moneda' => $MonedaUsd->id_moneda,
            'tipo' => 'EFECTIVO',
            'requiere_referencia' => false,
            'activo' => true,
        ]);

        MetodosPagoModel::create([
            'nombre' => 'Efectivo Bolívares',
            'codigo' => 'EFECTIVO_VES',
            'id_moneda' => $MonedaVes->id_moneda,
            'tipo' => 'EFECTIVO',
            'requiere_referencia' => false,
            'activo' => true,
        ]);

        MetodosPagoModel::create([
            'nombre' => 'Pago Móvil',
            'codigo' => 'PAGO_MOVIL_VES',
            'id_moneda' => $MonedaVes->id_moneda,
            'tipo' => 'DIGITAL',
            'requiere_referencia' => true,
            'activo' => true,
        ]);

        MetodosPagoModel::create([
            'nombre' => 'Punto de Venta',
            'codigo' => 'PUNTO_VENTA_VES',
            'id_moneda' => $MonedaVes->id_moneda,
            'tipo' => 'TARJETA',
            'requiere_referencia' => true,
            'activo' => true,
        ]);

        MetodosPagoModel::create([
            'nombre' => 'Zelle USD',
            'codigo' => 'ZELLE_USD',
            'id_moneda' => $MonedaUsd->id_moneda,
            'tipo' => 'TRANSFERENCIA',
            'requiere_referencia' => true,
            'activo' => true,
        ]);

        MetodosPagoModel::create([
            'nombre' => 'Cashea BNPL',
            'codigo' => 'CASHEA_USD',
            'id_moneda' => $MonedaUsd->id_moneda,
            'tipo' => 'FINANCIAMIENTO',
            'requiere_referencia' => true,
            'activo' => true,
        ]);

        MetodosPagoModel::create([
            'nombre' => 'Crédito Interno',
            'codigo' => 'CREDITO_USD',
            'id_moneda' => $MonedaUsd->id_moneda,
            'tipo' => 'CREDITO',
            'requiere_referencia' => false,
            'activo' => true,
        ]);

        // 5. Tipos de Movimiento de Inventario
        $TipoCompra = TiposMovimientoInventarioModel::create([
            'codigo' => 'ENTRADA_COMPRA',
            'nombre' => 'Entrada por Compra',
            'naturaleza' => 'ENTRADA',
            'descripcion' => 'Recepción de mercancía por factura de proveedor',
            'activo' => true,
        ]);

        TiposMovimientoInventarioModel::create([
            'codigo' => 'ENTRADA_AJUSTE',
            'nombre' => 'Entrada por Ajuste Manual',
            'naturaleza' => 'ENTRADA',
            'descripcion' => 'Ajuste manual para incrementar existencias',
            'activo' => true,
        ]);

        TiposMovimientoInventarioModel::create([
            'codigo' => 'SALIDA_VENTA',
            'nombre' => 'Salida por Venta POS',
            'naturaleza' => 'SALIDA',
            'descripcion' => 'Descargo automático de existencias al facturar',
            'activo' => true,
        ]);

        TiposMovimientoInventarioModel::create([
            'codigo' => 'SALIDA_MERMA',
            'nombre' => 'Salida por Merma / Daño',
            'naturaleza' => 'SALIDA',
            'descripcion' => 'Pérdida o producto deteriorado',
            'activo' => true,
        ]);

        TiposMovimientoInventarioModel::create([
            'codigo' => 'SALIDA_CONSUMO',
            'nombre' => 'Salida por Consumo Interno',
            'naturaleza' => 'SALIDA',
            'descripcion' => 'Uso interno para suministros de la tienda',
            'activo' => true,
        ]);

        TiposMovimientoInventarioModel::create([
            'codigo' => 'SALIDA_AJUSTE',
            'nombre' => 'Salida por Ajuste Manual',
            'naturaleza' => 'SALIDA',
            'descripcion' => 'Ajuste manual para reducir existencias',
            'activo' => true,
        ]);

        TiposMovimientoInventarioModel::create([
            'codigo' => 'AJUSTE_CONTEO',
            'nombre' => 'Conciliación por Conteo Físico',
            'naturaleza' => 'AJUSTE',
            'descripcion' => 'Ajuste generado al aplicar sesión de auditoría física',
            'activo' => true,
        ]);

        TiposMovimientoInventarioModel::create([
            'codigo' => 'DEVOLUCION',
            'nombre' => 'Devolución de Venta / Anulación',
            'naturaleza' => 'ENTRADA',
            'descripcion' => 'Reingreso de existencias por anulación o cambio de ticket',
            'activo' => true,
        ]);

        // 6. Unidades de Medida
        $UnidadUnd = UnidadesProductosModel::create([
            'nombre' => 'Unidad',
            'abreviatura' => 'UND',
            'permite_decimales' => false,
            'activo' => true,
        ]);

        $UnidadCj = UnidadesProductosModel::create([
            'nombre' => 'Caja',
            'abreviatura' => 'CJ',
            'permite_decimales' => false,
            'activo' => true,
        ]);

        $UnidadKg = UnidadesProductosModel::create([
            'nombre' => 'Kilogramo',
            'abreviatura' => 'KG',
            'permite_decimales' => true,
            'activo' => true,
        ]);

        $UnidadBlt = UnidadesProductosModel::create([
            'nombre' => 'Bulto',
            'abreviatura' => 'BLT',
            'permite_decimales' => false,
            'activo' => true,
        ]);

        UnidadesProductosModel::create([
            'nombre' => 'Litro',
            'abreviatura' => 'LT',
            'permite_decimales' => true,
            'activo' => true,
        ]);

        UnidadesProductosModel::create([
            'nombre' => 'Metro',
            'abreviatura' => 'M',
            'permite_decimales' => true,
            'activo' => true,
        ]);

        // 7. Marcas
        $MarcaSamsung = MarcasProductosModel::create(['nombre' => 'Samsung', 'descripcion' => 'Electrónica y telefonía']);
        $MarcaXiaomi = MarcasProductosModel::create(['nombre' => 'Xiaomi', 'descripcion' => 'Tecnología y accesorios']);
        $MarcaPolar = MarcasProductosModel::create(['nombre' => 'Empresas Polar', 'descripcion' => 'Alimentos y bebidas']);
        $MarcaNestle = MarcasProductosModel::create(['nombre' => 'Nestlé', 'descripcion' => 'Confitería y lácteos']);
        MarcasProductosModel::create(['nombre' => 'Genérico', 'descripcion' => 'Productos varios sin marca']);

        // 8. Categorías
        $CatTecnologia = ClasificacionProductosModel::create(['nombre' => 'Tecnología', 'descripcion' => 'Smartphones y periféricos']);
        $CatAlimentos = ClasificacionProductosModel::create(['nombre' => 'Alimentos y Bebidas', 'descripcion' => 'Víveres y refrigerios']);
        ClasificacionProductosModel::create(['nombre' => 'Hogar y Limpieza', 'descripcion' => 'Aseo y mantenimiento']);

        // 9. Clientes Demo
        ClientesModel::create([
            'identificacion' => 'V-00000000',
            'nombre' => 'Cliente General / Mostrador',
            'telefono' => '0000-0000000',
            'email' => 'mostrador@facilshop.com',
            'limite_credito' => 0.00,
            'saldo_pendiente' => 0.00,
        ]);

        ClientesModel::create([
            'identificacion' => 'V-18765432',
            'nombre' => 'Carlos Delgado',
            'telefono' => '0414-1234567',
            'email' => 'carlos.delgado@ejemplo.com',
            'limite_credito' => 500.00,
            'saldo_pendiente' => 0.00,
        ]);

        ClientesModel::create([
            'identificacion' => 'V-22334455',
            'nombre' => 'María Valentina Gómez',
            'telefono' => '0424-9876543',
            'email' => 'maria.gomez@ejemplo.com',
            'limite_credito' => 300.00,
            'saldo_pendiente' => 0.00,
        ]);

        // 10. Configuración Cashea
        CasheaConfigModel::create([
            'modo_operacion' => 'MANUAL',
            'api_key' => null,
            'api_secret' => null,
            'merchant_id' => 'CSH-MERCHANT-FACILSHOP',
            'porcentaje_inicial_defecto' => 40.00,
            'cuotas_defecto' => 3,
            'activo' => true,
        ]);

        // 11. Productos Demo con Unidades y Factores de Conversión
        $ProdSamsung = ProductosModel::create([
            'sku' => 'TEL-SAM-A54',
            'codigo_barras' => '8806091234567',
            'nombre' => 'Samsung Galaxy A54 5G 128GB',
            'modelo' => 'SM-A546E/DS',
            'descripcion' => 'Pantalla 6.4 FHD+ Super AMOLED 120Hz, Cámara 50MP OIS, Batería 5000mAh',
            'id_marca' => $MarcaSamsung->id_marca,
            'id_categoria' => $CatTecnologia->id_categoria,
            'id_unidad' => $UnidadUnd->id_unidad,
            'id_unidad_secundaria' => null,
            'equivalencia_unidad' => 1.0000,
            'equivalencia_unidad_secundaria' => 1.0000,
            'precio_costo' => 210.0000,
            'precio_venta' => 280.0000,
            'stock_actual' => 15,
            'stock_minimo' => 3,
            'imagen_principal' => null,
            'activo' => true,
        ]);

        $ProdHarina = ProductosModel::create([
            'sku' => 'ALM-HAR-PAN01',
            'codigo_barras' => '7591016000018',
            'nombre' => 'Harina PAN Maíz Blanco 1kg',
            'modelo' => 'Bulto 20x1kg',
            'descripcion' => 'Harina de maíz blanco precocida enriquecida 1kg',
            'id_marca' => $MarcaPolar->id_marca,
            'id_categoria' => $CatAlimentos->id_categoria,
            'id_unidad' => $UnidadBlt->id_unidad,
            'id_unidad_secundaria' => $UnidadUnd->id_unidad,
            'equivalencia_unidad' => 1.0000,
            'equivalencia_unidad_secundaria' => 20.0000, // 1 Bulto = 20 Paquetes
            'precio_costo' => 18.0000,
            'precio_venta' => 25.0000,
            'stock_actual' => 40,
            'stock_minimo' => 5,
            'imagen_principal' => null,
            'activo' => true,
        ]);

        $ProdRefresco = ProductosModel::create([
            'sku' => 'BEB-COC-2L',
            'codigo_barras' => '7591031000024',
            'nombre' => 'Coca Cola Original 2 Litros',
            'modelo' => 'Caja 6x2L',
            'descripcion' => 'Bebida gaseosa refrescante 2L Pet',
            'id_marca' => $MarcaPolar->id_marca,
            'id_categoria' => $CatAlimentos->id_categoria,
            'id_unidad' => $UnidadCj->id_unidad,
            'id_unidad_secundaria' => $UnidadUnd->id_unidad,
            'equivalencia_unidad' => 1.0000,
            'equivalencia_unidad_secundaria' => 6.0000, // 1 Caja = 6 Botellas
            'precio_costo' => 8.0000,
            'precio_venta' => 12.0000,
            'stock_actual' => 30,
            'stock_minimo' => 6,
            'imagen_principal' => null,
            'activo' => true,
        ]);

        // 12. Trazabilidad de Movimientos Iniciales de Inventario
        MovimientosInventarioModel::create([
            'id_producto' => $ProdSamsung->id_producto,
            'id_usuario' => $superadmin->id_usuario,
            'id_tipo_movimiento' => $TipoCompra->id_tipo_movimiento,
            'cantidad' => 15,
            'stock_anterior' => 0,
            'nuevo_stock' => 15,
            'motivo' => 'Inventario Inicial de Apertura',
            'documento_referencia' => 'INV-INIC-001',
            'created_at' => now(),
        ]);

        MovimientosInventarioModel::create([
            'id_producto' => $ProdHarina->id_producto,
            'id_usuario' => $superadmin->id_usuario,
            'id_tipo_movimiento' => $TipoCompra->id_tipo_movimiento,
            'cantidad' => 40,
            'stock_anterior' => 0,
            'nuevo_stock' => 40,
            'motivo' => 'Inventario Inicial de Apertura',
            'documento_referencia' => 'INV-INIC-001',
            'created_at' => now(),
        ]);

        MovimientosInventarioModel::create([
            'id_producto' => $ProdRefresco->id_producto,
            'id_usuario' => $superadmin->id_usuario,
            'id_tipo_movimiento' => $TipoCompra->id_tipo_movimiento,
            'cantidad' => 30,
            'stock_anterior' => 0,
            'nuevo_stock' => 30,
            'motivo' => 'Inventario Inicial de Apertura',
            'documento_referencia' => 'INV-INIC-001',
            'created_at' => now(),
        ]);
    }
}
