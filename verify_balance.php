<?php

use App\Models\Sale;
use App\Models\Expense;
use App\Models\DailyClosing;

echo "=== VERIFICACIÓN DE SALDO ACUMULADO ===\n\n";

// Verificar ventas totales
$totalSales = Sale::sum('total_amount');
echo "📊 Total ventas en BD: S/ {$totalSales}\n";

// Verificar gastos totales
$totalExpenses = Expense::sum('amount');
echo "📊 Total gastos en BD: S/ {$totalExpenses}\n";

// Verificar cierres diarios
$closingsCount = DailyClosing::count();
echo "\n📋 Cierres diarios registrados: {$closingsCount}\n";

if ($closingsCount > 0) {
    $lastClosing = DailyClosing::orderBy('closing_date', 'desc')->first();
    echo "📅 Último cierre: {$lastClosing->closing_date}\n";
    echo "💰 Saldo final del último cierre: S/ {$lastClosing->final_balance}\n";
} else {
    echo "\n⚠️ NO HAY CIERRES DIARIOS - Creando uno ahora...\n";
    
    // Obtener fecha de hoy
    $today = now()->format('Y-m-d');
    
    // Sumar todas las ventas hasta hoy
    $allSales = Sale::whereDate('sale_date', '<=', $today)->sum('total_amount');
    $allExpenses = Expense::whereDate('expense_date', '<=', $today)->sum('amount');
    
    // Crear cierre acumulativo
    $closing = DailyClosing::create([
        'closing_date' => $today,
        'total_sales' => $allSales,
        'total_expenses' => $allExpenses,
        'previous_balance' => 0,
        'final_balance' => $allSales - $allExpenses,
        'user_id' => 1
    ]);
    
    echo "\n✅ Cierre creado exitosamente!\n";
    echo "💰 Saldo acumulado: S/ {$closing->final_balance}\n";
    echo "\n🔄 Ahora recarga la página de Registro Diario.\n";
}

echo "\n=== FIN ===\n";
