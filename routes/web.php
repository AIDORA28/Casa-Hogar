<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Registro Diario de Tesorería (Principal)
    Route::get('/daily-registry', function () {
        return Inertia::render('DailyRegistry');
    })->name('daily-registry');

    // POS Original (Opcional)
    Route::get('/pos', function () {
        return Inertia::render('POS');
    })->name('pos');

    // Gestión de Inventario (Solo Admin)
    Route::get('/inventory', function () {
        return Inertia::render('InventoryManager');
    })->middleware('role:admin')->name('inventory');

    // Reportes (Solo Admin)
    Route::get('/reports', function () {
        return Inertia::render('Reports');
    })->middleware('role:admin')->name('reports');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
