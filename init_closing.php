<?php

use Illuminate\Support\Facades\DB;
use App\Models\Sale;
use App\Models\Expense;
use App\Models\DailyClosing;

// Obtener la fecha de hoy
$today = now()->format('Y-m-d');

// Calcular totales de ventas y gastos de hoy
$totalSales = Sale::whereDate('sale_date', $today)->sum('total_amount');
$totalExpenses = Expense::whereDate('expense_date', $today)->sum('amount');

echo "📊 Creando cierre diario retroactivo\n";
echo "Fecha: {$today}\n";
echo "Total Ventas: S/ {$totalSales}\n";
echo "Total Gastos: S/ {$totalExpenses}\n";

// Verificar si ya existe un cierre para hoy
$existing = DailyClosing::where('closing_date', $today)->first();

if ($existing) {
    echo "\n⚠️ Ya existe un cierre para hoy. Actualizando...\n";
    $existing->update([
        'total_sales' => $totalSales,
        'total_expenses' => $totalExpenses,
        'final_balance' => $totalSales - $totalExpenses
    ]);
    $closing = $existing;
} else {
    // Crear nuevo cierre
    $closing = DailyClosing::create([
        'closing_date' => $today,
        'total_sales' => $totalSales,
        'total_expenses' => $totalExpenses,
        'previous_balance' => 0,
        'final_balance' => $totalSales - $totalExpenses,
        'user_id' => 1
    ]);
    echo "\n✅ Cierre diario creado!\n";
}

echo "\nSaldo Final: S/ {$closing->final_balance}\n";
echo "\n🔄 Ahora recarga la página de Registro Diario y verás el saldo inicial correcto.\n";
