<?php

use App\Http\Controllers\CajaTurnosController;
use App\Http\Controllers\MetodosPagoController;
use App\Http\Controllers\MonedasController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function ()
{
    // Monedas y Tasas
    Route::get('/currencies', [MonedasController::class, 'index']);
    Route::post('/currencies/rates', [MonedasController::class, 'actualizarTasas']);
    Route::get('/currencies/{id_moneda}/history', [MonedasController::class, 'historico']);

    // Métodos de Pago
    Route::get('/payment-methods', [MetodosPagoController::class, 'index']);
    Route::post('/payment-methods', [MetodosPagoController::class, 'store']);
    Route::put('/payment-methods/{id_metodo_pago}', [MetodosPagoController::class, 'update']);

    // Turnos de Caja
    Route::post('/shifts/open', [CajaTurnosController::class, 'abrirTurno']);
    Route::post('/shifts/close/{id_caja_turno}', [CajaTurnosController::class, 'cerrarTurno']);
    Route::get('/shifts/current', [CajaTurnosController::class, 'current']);

    // Inventario: Productos
    Route::get('/products', [App\Http\Controllers\ProductosController::class, 'ApiListar']);
    Route::get('/products/barcode', [App\Http\Controllers\ProductosController::class, 'ApiBuscarPorCodigo']);

    // Inventario: Ajustes
    Route::post('/inventory/adjustments', [App\Http\Controllers\InventarioAjustesController::class, 'ApiStore']);
    Route::get('/inventory/adjustments/types/{naturaleza}', [App\Http\Controllers\InventarioAjustesController::class, 'ApiTipos']);

    // Punto de Venta (POS)
    Route::post('/pos/checkout', [App\Http\Controllers\PosController::class, 'ApiCheckout']);
    Route::post('/pos/simulate-cashea', [App\Http\Controllers\PosController::class, 'SimularCashea']);
});
