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
});

Route::middleware('auth')->group(function ()
{
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
