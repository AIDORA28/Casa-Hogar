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

    // Gestión de Inventario (requiere permiso manage_inventory)
    Route::get('/inventory', function () {
        if (!auth()->user()->permissions->contains('name', 'manage_inventory')) {
            abort(403, 'No tiene permisos para acceder a este recurso.');
        }
        return Inertia::render('InventoryManager');
    })->name('inventory');

    // Reportes (requiere permiso download_reports)
    Route::get('/reports', function () {
        if (!auth()->user()->permissions->contains('name', 'download_reports')) {
            abort(403, 'No tiene permisos para acceder a este recurso.');
        }
        return Inertia::render('Reports');
    })->name('reports');

    // Gestión de Usuarios (Solo Admin)
    Route::get('/users', function () {
        return Inertia::render('UserManagement');
    })->middleware('role:admin')->name('users');

    // Gestión de Personal/Enfermeras (Solo Admin)
    Route::get('/nurses', function () {
        return Inertia::render('Nurses');
    })->middleware('role:admin')->name('nurses');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
