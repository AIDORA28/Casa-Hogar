<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DailyClosingController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ============================================
// RUTAS PÚBLICAS (Sin Autenticación)
// ============================================

// Autenticación con Rate Limiting (5 intentos por minuto)
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// ============================================
// RUTAS PROTEGIDAS (Requieren Autenticación)
// ============================================

Route::middleware('auth:sanctum')->group(function () {
    
    // Información del usuario autenticado
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // ============================================
    // PRODUCTOS
    // ============================================
    
    // Listar productos (Admin y Tesorero)
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    
    // Crear, actualizar y eliminar productos (Solo Admin)
    Route::post('/products', [ProductController::class, 'store'])->middleware('role:admin');
    Route::put('/products/{id}', [ProductController::class, 'update'])->middleware('role:admin');
    Route::delete('/products/{id}', [ProductController::class, 'destroy'])->middleware('role:admin');

    // ============================================
    // VENTAS
    // ============================================
    
    // Listar y ver ventas (Admin y Tesorero)
    Route::get('/sales', [SaleController::class, 'index']);
    Route::get('/sales/{id}', [SaleController::class, 'show']);
    
    // Crear venta (Admin y Tesorero)
    Route::post('/sales', [SaleController::class, 'store']);

    // ============================================
    // GASTOS
    // ============================================
    
    // Listar y ver gastos (Admin y Tesorero)
    Route::get('/expenses', [ExpenseController::class, 'index']);
    Route::get('/expenses/{id}', [ExpenseController::class, 'show']);
    
    // Crear gasto (Admin y Tesorero)
    Route::post('/expenses', [ExpenseController::class, 'store']);
    
    // Actualizar y eliminar gastos (Solo Admin)
    Route::put('/expenses/{id}', [ExpenseController::class, 'update'])->middleware('role:admin');
    Route::delete('/expenses/{id}', [ExpenseController::class, 'destroy'])->middleware('role:admin');

    // ============================================
    // CIERRES DIARIOS
    // ============================================
    
    // Listar y ver cierres (Admin)
    Route::get('/daily-closings', [DailyClosingController::class, 'index'])->middleware('role:admin');
    Route::get('/daily-closings/{id}', [DailyClosingController::class, 'show'])->middleware('role:admin');
    
    // Calcular cierre (Admin)
    Route::post('/daily-closings/calculate', [DailyClosingController::class, 'calculate'])->middleware('role:admin');
    
    // Obtener último saldo (para arrastre automático)
    Route::get('/daily-closings/last-balance', [DailyClosingController::class, 'getLastBalance']);

    // ============================================
    // INYECCIONES DE CAPITAL
    // ============================================
    
    // Registrar inyección de capital (Solo Admin)
    Route::post('/capital-injections', [CapitalInjectionController::class, 'store'])->middleware('role:admin');
    Route::get('/capital-injections', [CapitalInjectionController::class, 'index'])->middleware('role:admin');
    Route::get('/capital-injections/date/{date}', [CapitalInjectionController::class, 'getByDate'])->middleware('role:admin');

    // ============================================
    // REPORTES (Solo Admin)
    // ============================================
    
    // Dashboard stats
    Route::get('/reports/dashboard', [ReportController::class, 'getDashboardStats'])->middleware('role:admin');
    
    // Reportes por rango de fechas
    Route::get('/reports/sales-by-date', [ReportController::class, 'getSalesByDateRange'])->middleware('role:admin');
    
    // PDF cierre diario (on-demand)
    Route::get('/reports/daily-closing-pdf/{date}', [ReportController::class, 'generateDailyClosingPDF'])->middleware('role:admin');
    
    // Activity logs
    Route::get('/reports/activity-logs', [ReportController::class, 'getActivityLogs'])->middleware('role:admin');
    
    // Desglose detallado del día
    Route::get('/reports/daily-detail/{date}', [ReportController::class, 'getDailyDetail'])->middleware('role:admin');
});
