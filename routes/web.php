<?php

use App\Http\Controllers\CajaTurnosController;
use App\Http\Controllers\MetodosPagoController;
use App\Http\Controllers\MonedasController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function ()
{
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified'])->group(function ()
{
    Route::get('/dashboard', function ()
    {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Monedas y Tasas
    Route::get('/monedas', [MonedasController::class, 'index'])->name('monedas.index');
    Route::post('/monedas/tasas', [MonedasController::class, 'actualizarTasas'])->name('monedas.tasas.actualizar');
    Route::get('/monedas/{id_moneda}/historico', [MonedasController::class, 'historico'])->name('monedas.historico');

    // Métodos de Pago
    Route::get('/metodos-pago', [MetodosPagoController::class, 'index'])->name('metodos-pago.index');
    Route::post('/metodos-pago', [MetodosPagoController::class, 'store'])->name('metodos-pago.store');
    Route::put('/metodos-pago/{id_metodo_pago}', [MetodosPagoController::class, 'update'])->name('metodos-pago.update');
    Route::patch('/metodos-pago/{id_metodo_pago}/estado', [MetodosPagoController::class, 'alternarEstado'])->name('metodos-pago.estado');

    // Turnos de Caja
    Route::get('/caja', [CajaTurnosController::class, 'index'])->name('caja.index');
    Route::post('/caja/abrir', [CajaTurnosController::class, 'abrirTurno'])->name('caja.abrir');
    Route::post('/caja/{id_caja_turno}/cerrar', [CajaTurnosController::class, 'cerrarTurno'])->name('caja.cerrar');
    Route::get('/caja/activo', [CajaTurnosController::class, 'current'])->name('caja.activo');

    // Inventario: Productos
    Route::get('/inventario/productos', [App\Http\Controllers\ProductosController::class, 'Index'])->name('inventario.productos.index');
    Route::post('/inventario/productos', [App\Http\Controllers\ProductosController::class, 'Store'])->name('inventario.productos.store');
    Route::put('/inventario/productos/{id_producto}', [App\Http\Controllers\ProductosController::class, 'Update'])->name('inventario.productos.update');
    Route::get('/inventario/productos/{id_producto}/movimientos', [App\Http\Controllers\ProductosController::class, 'Movimientos'])->name('inventario.productos.movimientos');

    // Inventario: Ajustes
    Route::get('/inventario/ajustes', [App\Http\Controllers\InventarioAjustesController::class, 'Index'])->name('inventario.ajustes.index');
    Route::get('/inventario/ajustes/crear', [App\Http\Controllers\InventarioAjustesController::class, 'Create'])->name('inventario.ajustes.create');
    Route::post('/inventario/ajustes', [App\Http\Controllers\InventarioAjustesController::class, 'Store'])->name('inventario.ajustes.store');
    Route::get('/inventario/ajustes/{id_ajuste}', [App\Http\Controllers\InventarioAjustesController::class, 'Show'])->name('inventario.ajustes.show');
    Route::get('/inventario/ajustes/tipos/{naturaleza}', [App\Http\Controllers\InventarioAjustesController::class, 'ApiTipos'])->name('inventario.ajustes.tipos');

    // Inventario: Conteos Físicos
    Route::get('/inventario/conteos', [App\Http\Controllers\InventarioConteosController::class, 'Index'])->name('inventario.conteos.index');
    Route::post('/inventario/conteos', [App\Http\Controllers\InventarioConteosController::class, 'Store'])->name('inventario.conteos.store');
    Route::get('/inventario/conteos/{id_conteo}', [App\Http\Controllers\InventarioConteosController::class, 'Worksheet'])->name('inventario.conteos.worksheet');
    Route::post('/inventario/conteos/{id_conteo}/detalles', [App\Http\Controllers\InventarioConteosController::class, 'ActualizarDetalles'])->name('inventario.conteos.detalles');
    Route::post('/inventario/conteos/{id_conteo}/aplicar', [App\Http\Controllers\InventarioConteosController::class, 'Aplicar'])->name('inventario.conteos.aplicar');
    Route::post('/inventario/conteos/{id_conteo}/cancelar', [App\Http\Controllers\InventarioConteosController::class, 'Cancelar'])->name('inventario.conteos.cancelar');

    // Punto de Venta (POS)
    Route::get('/pos', [App\Http\Controllers\PosController::class, 'Index'])->name('pos.index');
    Route::post('/pos/checkout', [App\Http\Controllers\PosController::class, 'Checkout'])->name('pos.checkout');
    Route::post('/pos/simular-cashea', [App\Http\Controllers\PosController::class, 'SimularCashea'])->name('pos.cashea.simular');
    Route::get('/pos/ticket/{id_venta}', [App\Http\Controllers\PosController::class, 'Ticket'])->name('pos.ticket');
});

Route::middleware('auth')->group(function ()
{
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
