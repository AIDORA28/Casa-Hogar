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
    
    // ENDPOINT TEMPORAL - Bypass DailyClosingController
    Route::get('/daily-closings/last-balance', function() {
        try {
            $result = DB::table('daily_closings')
                ->orderBy('closing_date', 'desc')
                ->first();
            
            return response()->json([
                'last_balance' => $result ? (float)$result->final_balance : 0.00,
                'last_date' => $result ? $result->closing_date : null
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en endpoint temporal: ' . $e->getMessage());
            return response()->json([
                'last_balance' => 0.00,
                'last_date' => null
            ]);
        }
    });
    
    // ENDPOINT TEMPORAL - Calcular/Actualizar cierre diario
    Route::post('/daily-closings/calculate', function(Request $request) {
        try {
            $closingDate = $request->closing_date;
            
            // Calcular totales del día
            $totalSales = DB::table('sales')
                ->whereDate('sale_date', $closingDate)
                ->sum('total_amount');
            
            $totalExpenses = DB::table('expenses')
                ->whereDate('expense_date', $closingDate)
                ->sum('amount');
            
            // *** INCLUIR INYECCIONES DE CAPITAL ***
            $totalInjections = DB::table('capital_injections')
                ->whereDate('injection_date', $closingDate)
                ->sum('amount');
            
            // Obtener saldo anterior
            $previousClosing = DB::table('daily_closings')
                ->where('closing_date', '<', $closingDate)
                ->orderBy('closing_date', 'desc')
                ->first();
            
            $previousBalance = $previousClosing ? $previousClosing->final_balance : 0;
            
            // SALDO FINAL = (Ventas - Gastos + Inyecciones) + Saldo Anterior
            $finalBalance = ($totalSales - $totalExpenses + $totalInjections) + $previousBalance;
            
            // Verificar si ya existe un cierre para esta fecha
            $existing = DB::table('daily_closings')
                ->where('closing_date', $closingDate)
                ->first();
            
            if ($existing) {
                // Actualizar cierre existente
                DB::table('daily_closings')
                    ->where('closing_date', $closingDate)
                    ->update([
                        'total_sales' => $totalSales,
                        'total_expenses' => $totalExpenses,
                        'previous_balance' => $previousBalance,
                        'final_balance' => $finalBalance,
                        'updated_at' => now()
                    ]);
            } else {
                // Crear nuevo cierre
                DB::table('daily_closings')->insert([
                    'closing_date' => $closingDate,
                    'total_sales' => $totalSales,
                    'total_expenses' => $totalExpenses,
                    'previous_balance' => $previousBalance,
                    'final_balance' => $finalBalance,
                    'user_id' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
            
            \Log::info("Cierre calculado - Ventas: $totalSales, Gastos: $totalExpenses, Inyecciones: $totalInjections, Final: $finalBalance");
            
            return response()->json([
                'message' => 'Cierre calculado exitosamente',
                'final_balance' => $finalBalance,
                'total_injections' => $totalInjections
            ], 201);
            
        } catch (\Exception $e) {
            \Log::error('Error en calculate: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al calcular cierre',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    
    // ENDPOINT TEMPORAL - Guardar inyección de capital
    Route::post('/capital-injections', function(Request $request) {
        try {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'reason' => 'required|string|max:255',
                'injection_date' => 'required|date'
            ]);
            
            $injectionId = DB::table('capital_injections')->insertGetId([
                'injection_date' => $validated['injection_date'],
                'amount' => $validated['amount'],
                'reason' => $validated['reason'],
                'user_id' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Registrar en activity log
            DB::table('activity_logs')->insert([
                'user_id' => auth()->id(),
                'action' => 'created',
                'resource_type' => 'CapitalInjection',
                'resource_id' => $injectionId,
                'details' => json_encode($validated),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            return response()->json([
                'message' => 'Inyección de capital registrada exitosamente',
                'injection' => array_merge($validated, ['id' => $injectionId])
            ], 201);
            
        } catch (\Exception $e) {
            \Log::error('Error en capital-injections: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al guardar inyección de capital',
                'error' => $e->getMessage()
            ], 400);
        }
    });
    
    // TEMPORALMENTE COMENTADO - Debugging error 500
    /*
    // Listar y ver cierres (Admin)
    Route::get('/daily-closings', [DailyClosingController::class, 'index'])->middleware('role:admin');
    Route::get('/daily-closings/{id}', [DailyClosingController::class, 'show'])->middleware('role:admin');
    
    // Calcular cierre (Admin)
    Route::post('/daily-closings/calculate', [DailyClosingController::class, 'calculate'])->middleware('role:admin');
    
    // Obtener último saldo (para arrastre automático)
    Route::get('/daily-closings/last-balance', [DailyClosingController::class, 'getLastBalance']);
    */

    // ============================================
    // INYECCIONES DE CAPITAL
    // ============================================
    
    // TEMPORALMENTE COMENTADO - Causando error 500
    // TODO: Descomentar cuando se arregle el issue del autoloader
    /*
    Route::post('/capital-injections', [CapitalInjectionController::class, 'store'])->middleware('role:admin');
    Route::get('/capital-injections', [CapitalInjectionController::class, 'index'])->middleware('role:admin');
    Route::get('/capital-injections/date/{date}', [CapitalInjectionController::class, 'getByDate'])->middleware('role:admin');
    */

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
